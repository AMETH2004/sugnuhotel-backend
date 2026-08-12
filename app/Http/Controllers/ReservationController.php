<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Mail\ReservationAnnuleeMail;
use App\Mail\ReservationConfirmeeMail;
use App\Mail\ReservationModifieeMail;
use App\Models\Chambre;
use App\Models\Reservation;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    /**
     * Liste des réservations.
     * - Client : uniquement les siennes (avec filtre a_venir/en_cours/passees).
     * - Réception/Admin : toutes, avec recherche (nom, numéro, date, chambre).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $estPersonnel = $user->hasAnyRole(['Administrateur', 'Receptionniste']);

        $query = Reservation::with(['chambre.typeChambre', 'user', 'services']);

        if (!$estPersonnel) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('recherche')) {
            $terme = $request->input('recherche');
            $query->where(function ($q) use ($terme) {
                $q->where('numero_reservation', 'like', "%{$terme}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$terme}%"))
                    ->orWhereHas('chambre', fn ($c) => $c->where('numero_chambre', 'like', "%{$terme}%"));
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date_arrivee', $request->input('date'));
        }

        // Utilisé par la vue calendrier : toutes les réservations chevauchant une période
        // donnée (ex : un mois affiché), quel que soit leur statut sauf annulées.
        if ($request->filled('periode_debut') && $request->filled('periode_fin')) {
            $query->whereNotIn('statut', [Reservation::STATUT_ANNULEE])
                ->where('date_arrivee', '<', $request->input('periode_fin'))
                ->where('date_depart', '>', $request->input('periode_debut'));
        }

        match ($request->input('periode')) {
            'a_venir' => $query->aVenir(),
            'en_cours' => $query->enCours(),
            'passees' => $query->passees(),
            default => null,
        };

        $parPage = min((int) $request->input('par_page', 20), 500);

        return response()->json($query->orderByDesc('date_arrivee')->paginate($parPage));
    }

    public function show(Request $request, Reservation $reservation)
    {
        $this->autoriserAcces($request, $reservation);

        return response()->json($reservation->load(['chambre.typeChambre', 'user', 'services']));
    }

    /**
     * Création d'une réservation.
     *
     * Le défi du double-booking est traité ici : on ouvre une transaction, on verrouille
     * la ligne "chambre" (SELECT ... FOR UPDATE) pour bloquer toute création concurrente
     * sur la même chambre, puis on revérifie l'absence de chevauchement de dates avant
     * d'insérer. Sans ce verrou, deux requêtes simultanées pourraient toutes les deux
     * passer la validation initiale et créer deux réservations pour la même période.
     */
    public function store(StoreReservationRequest $request)
    {
        $user = $request->user();
        $estPersonnel = $user->hasAnyRole(['Administrateur', 'Receptionniste']);

        // Le personnel peut réserver pour un client existant ; sinon c'est le client connecté.
        $clientId = $estPersonnel && $request->filled('user_id') ? $request->input('user_id') : $user->id;

        $reservation = DB::transaction(function () use ($request, $clientId) {
                $chambre = Chambre::lockForUpdate()->findOrFail($request->input('chambre_id'));

                if (!$chambre->estDisponiblePour($request->input('date_arrivee'), $request->input('date_depart'))) {
                    abort(409, 'Cette chambre vient d\'être réservée par quelqu\'un d\'autre pour cette période. Merci de choisir une autre chambre ou d\'autres dates.');
                }

                $nombreNuits = (new \DateTime($request->input('date_arrivee')))
                    ->diff(new \DateTime($request->input('date_depart')))->days;

                $prixServices = 0;
                $servicesDemandes = collect($request->input('services', []));

                if ($servicesDemandes->isNotEmpty()) {
                    $services = Service::whereIn('id', $servicesDemandes->pluck('service_id'))->get()->keyBy('id');
                    foreach ($servicesDemandes as $s) {
                        $service = $services->get($s['service_id']);
                        $quantite = $s['quantite'] ?? 1;
                        $prixServices += $service->prix * $quantite;
                    }
                }

                $prixTotal = ($chambre->prix_par_nuit * $nombreNuits) + $prixServices;

                $reservation = Reservation::create([
                    'numero_reservation' => $this->genererNumeroReservation(),
                    'user_id' => $clientId,
                    'chambre_id' => $chambre->id,
                    'date_arrivee' => $request->input('date_arrivee'),
                    'date_depart' => $request->input('date_depart'),
                    'nombre_adultes' => $request->input('nombre_adultes'),
                    'nombre_enfants' => $request->input('nombre_enfants', 0),
                    'prix_total' => $prixTotal,
                    'statut' => Reservation::STATUT_CONFIRMEE,
                    'demandes_speciales' => $request->input('demandes_speciales'),
                ]);

                foreach ($servicesDemandes as $s) {
                    $service = $services->get($s['service_id']);
                    $quantite = $s['quantite'] ?? 1;
                    $reservation->serviceReservations()->create([
                        'service_id' => $service->id,
                        'quantite' => $quantite,
                        'prix' => $service->prix * $quantite,
                    ]);
                }

                return $reservation;
            });

        $reservation->load(['chambre.typeChambre', 'user', 'services']);

        $this->envoyerEmailSiPossible(fn () => Mail::to($reservation->user->email)->send(new ReservationConfirmeeMail($reservation)));

        return response()->json([
            'message' => 'Réservation créée avec succès.',
            'data' => $reservation,
        ], 201);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation)
    {
        $this->autoriserAcces($request, $reservation);

        $ancienStatut = $reservation->statut;
        $reservation->update($request->validated());

        // Si les dates ou la chambre changent, le prix total doit être recalculé.
        if ($request->hasAny(['date_arrivee', 'date_depart', 'chambre_id'])) {
            $this->recalculerPrix($reservation);
        }

        $reservation->refresh()->load(['chambre.typeChambre', 'user', 'services']);

        if ($reservation->statut === Reservation::STATUT_ANNULEE && $ancienStatut !== Reservation::STATUT_ANNULEE) {
            $this->envoyerEmailSiPossible(fn () => Mail::to($reservation->user->email)->send(new ReservationAnnuleeMail($reservation)));
        } else {
            $this->envoyerEmailSiPossible(fn () => Mail::to($reservation->user->email)->send(new ReservationModifieeMail($reservation)));
        }

        return response()->json([
            'message' => 'Réservation mise à jour avec succès.',
            'data' => $reservation,
        ]);
    }

    /**
     * Annulation d'une réservation (client ou personnel).
     * Règle métier : un client ne peut plus annuler une réservation déjà enregistrée (check-in fait) ou terminée.
     */
    public function annuler(Request $request, Reservation $reservation)
    {
        $this->autoriserAcces($request, $reservation);

        $estPersonnel = $request->user()->hasAnyRole(['Administrateur', 'Receptionniste']);

        if (!$estPersonnel && in_array($reservation->statut, [Reservation::STATUT_ENREGISTREE, Reservation::STATUT_TERMINEE])) {
            return response()->json([
                'message' => 'Cette réservation ne peut plus être annulée (séjour déjà commencé ou terminé).',
            ], 422);
        }

        $reservation->update(['statut' => Reservation::STATUT_ANNULEE]);

        $this->envoyerEmailSiPossible(fn () => Mail::to($reservation->user->email)->send(new ReservationAnnuleeMail($reservation)));

        return response()->json(['message' => 'Réservation annulée avec succès.']);
    }

    /**
     * Check-in : la chambre passe "occupée", la réservation "enregistrée".
     * Réservé au personnel (voir middleware de rôle sur la route).
     */
    public function checkIn(Reservation $reservation)
    {
        if ($reservation->statut !== Reservation::STATUT_CONFIRMEE) {
            return response()->json(['message' => 'Seule une réservation confirmée peut faire l\'objet d\'un check-in.'], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['statut' => Reservation::STATUT_ENREGISTREE]);
            $reservation->chambre()->update(['statut' => Chambre::STATUT_OCCUPEE]);
        });

        return response()->json(['message' => 'Check-in effectué avec succès.', 'data' => $reservation->fresh()]);
    }

    /**
     * Check-out : la chambre redevient disponible, la réservation "terminée".
     */
    public function checkOut(Reservation $reservation)
    {
        if ($reservation->statut !== Reservation::STATUT_ENREGISTREE) {
            return response()->json(['message' => 'Seule une réservation enregistrée (check-in fait) peut faire l\'objet d\'un check-out.'], 422);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update(['statut' => Reservation::STATUT_TERMINEE]);
            $reservation->chambre()->update(['statut' => Chambre::STATUT_DISPONIBLE]);
        });

        return response()->json(['message' => 'Check-out effectué avec succès.', 'data' => $reservation->fresh()]);
    }

    private function autoriserAcces(Request $request, Reservation $reservation): void
    {
        $user = $request->user();
        $estPersonnel = $user->hasAnyRole(['Administrateur', 'Receptionniste']);

        abort_unless($estPersonnel || $reservation->user_id === $user->id, 403, 'Accès non autorisé à cette réservation.');
    }

    private function genererNumeroReservation(): string
    {
        do {
            $numero = 'RES-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
        } while (Reservation::where('numero_reservation', $numero)->exists());

        return $numero;
    }

    private function recalculerPrix(Reservation $reservation): void
    {
        $nombreNuits = (new \DateTime($reservation->date_arrivee))
            ->diff(new \DateTime($reservation->date_depart))->days;

        $prixServices = $reservation->serviceReservations()->sum('prix');

        $reservation->update([
            'prix_total' => ($reservation->chambre->prix_par_nuit * $nombreNuits) + $prixServices,
        ]);
    }

    /**
     * Les envois d'email ne doivent jamais faire échouer la requête HTTP
     * (ex : Mailtrap non configuré en local) — on journalise l'erreur et on continue.
     */
    private function envoyerEmailSiPossible(\Closure $envoi): void
    {
        try {
            $envoi();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

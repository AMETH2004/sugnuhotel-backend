<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chambre\StoreChambreRequest;
use App\Http\Requests\Chambre\UpdateChambreRequest;
use App\Models\Chambre;
use App\Models\RoomAmenity;
use App\Models\RoomImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ChambreController extends Controller
{
    /**
     * Liste des chambres (Admin/Réception), avec filtres optionnels.
     */
    public function index(Request $request)
    {
        $query = Chambre::with(['typeChambre', 'images']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('type_chambre_id')) {
            $query->where('type_chambre_id', $request->input('type_chambre_id'));
        }

        return response()->json($query->orderBy('numero_chambre')->get());
    }

    /**
     * Étape "Disponibilité" du parcours client : recherche des chambres libres
     * sur une période donnée, avec capacité suffisante, et calcul du prix total.
     */
    public function disponibles(Request $request)
    {
        $validated = $request->validate([
            'date_arrivee' => 'required|date|after_or_equal:today',
            'date_depart' => 'required|date|after:date_arrivee',
            'nombre_personnes' => 'sometimes|integer|min:1',
            'type_chambre_id' => 'sometimes|integer|exists:type_chambres,id',
        ]);

        $nombrePersonnes = $validated['nombre_personnes'] ?? 1;
        $nombreNuits = (new \DateTime($validated['date_arrivee']))
            ->diff(new \DateTime($validated['date_depart']))->days;

        $chambres = Chambre::with(['typeChambre', 'images'])
            ->where('statut', Chambre::STATUT_DISPONIBLE)
            ->where('capacite_max', '>=', $nombrePersonnes)
            ->when($request->filled('type_chambre_id'), fn ($q) => $q->where('type_chambre_id', $validated['type_chambre_id']))
            ->whereDoesntHave('reservations', function ($q) use ($validated) {
                $q->whereNotIn('statut', ['annulee'])
                    ->where('date_arrivee', '<', $validated['date_depart'])
                    ->where('date_depart', '>', $validated['date_arrivee']);
            })
            ->orderBy('prix_par_nuit')
            ->get()
            ->map(function (Chambre $chambre) use ($nombreNuits) {
                $chambre->prix_total_sejour = round($chambre->prix_par_nuit * $nombreNuits, 2);
                $chambre->nombre_nuits = $nombreNuits;
                return $chambre;
            });

        return response()->json($chambres);
    }

    public function show(Chambre $chambre)
    {
        return response()->json($chambre->load(['typeChambre', 'images', 'amenities']));
    }

    public function store(StoreChambreRequest $request)
    {
        $data = $request->safe()->except(['photos', 'amenities']);
        $chambre = Chambre::create($data);

        $this->attacherPhotos($request, $chambre);
        $this->attacherAmenities($request, $chambre);

        return response()->json([
            'message' => 'Chambre créée avec succès.',
            'data' => $chambre->load(['typeChambre', 'images', 'amenities']),
        ], 201);
    }

    public function update(UpdateChambreRequest $request, Chambre $chambre)
    {
        $data = $request->safe()->except(['photos', 'amenities']);
        $chambre->update($data);

        $this->attacherPhotos($request, $chambre);
        $this->attacherAmenities($request, $chambre);

        return response()->json([
            'message' => 'Chambre mise à jour avec succès.',
            'data' => $chambre->load(['typeChambre', 'images', 'amenities']),
        ]);
    }

    /**
     * Changement rapide de statut (utilisé par la réception : maintenance, hors service...).
     */
    public function changerStatut(Request $request, Chambre $chambre)
    {
        $validated = $request->validate([
            'statut' => ['required', Rule::in([
                Chambre::STATUT_DISPONIBLE,
                Chambre::STATUT_OCCUPEE,
                Chambre::STATUT_MAINTENANCE,
                Chambre::STATUT_HORS_SERVICE,
            ])],
        ]);

        $chambre->update($validated);

        return response()->json([
            'message' => 'Statut de la chambre mis à jour.',
            'data' => $chambre,
        ]);
    }

    public function destroy(Chambre $chambre)
    {
        if ($chambre->reservations()->actives()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : cette chambre a des réservations actives.',
            ], 422);
        }

        foreach ($chambre->images as $image) {
            Storage::disk('public')->delete($image->chemin);
        }

        $chambre->delete();

        return response()->json(['message' => 'Chambre supprimée avec succès.']);
    }

    /**
     * Supprime une photo précise de la galerie d'une chambre.
     */
    public function supprimerPhoto(Chambre $chambre, RoomImage $image)
    {
        if ($image->chambre_id !== $chambre->id) {
            return response()->json(['message' => 'Cette photo n\'appartient pas à cette chambre.'], 404);
        }

        Storage::disk('public')->delete($image->chemin);
        $image->delete();

        return response()->json(['message' => 'Photo supprimée avec succès.']);
    }

    private function attacherPhotos(Request $request, Chambre $chambre): void
    {
        if (!$request->hasFile('photos')) {
            return;
        }

        $dejaUnePrincipale = $chambre->images()->where('est_principale', true)->exists();

        foreach ($request->file('photos') as $index => $photo) {
            $chemin = $photo->store('chambres', 'public');

            RoomImage::create([
                'chambre_id' => $chambre->id,
                'chemin' => $chemin,
                'est_principale' => !$dejaUnePrincipale && $index === 0,
                'ordre' => $chambre->images()->count(),
            ]);
        }
    }

    private function attacherAmenities(Request $request, Chambre $chambre): void
    {
        if (!$request->filled('amenities')) {
            return;
        }

        foreach ($request->input('amenities') as $amenity) {
            RoomAmenity::firstOrCreate([
                'chambre_id' => $chambre->id,
                'amenity_name' => $amenity,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Tableau de bord Réception/Admin : vue d'ensemble du jour.
     */
    public function index()
    {
        $aujourdhui = Carbon::today();

        $arrivees = Reservation::with(['chambre', 'user'])
            ->whereDate('date_arrivee', $aujourdhui)
            ->where('statut', Reservation::STATUT_CONFIRMEE)
            ->get();

        $departs = Reservation::with(['chambre', 'user'])
            ->whereDate('date_depart', $aujourdhui)
            ->where('statut', Reservation::STATUT_ENREGISTREE)
            ->get();

        $totalChambres = Chambre::count();
        $chambresOccupees = Chambre::where('statut', Chambre::STATUT_OCCUPEE)->count();

        return response()->json([
            'arrivees_du_jour' => $arrivees,
            'departs_du_jour' => $departs,
            'reservations_en_attente' => Reservation::where('statut', Reservation::STATUT_EN_ATTENTE)->count(),
            'chambres' => [
                'total' => $totalChambres,
                'disponibles' => Chambre::where('statut', Chambre::STATUT_DISPONIBLE)->count(),
                'occupees' => $chambresOccupees,
                'maintenance' => Chambre::where('statut', Chambre::STATUT_MAINTENANCE)->count(),
                'hors_service' => Chambre::where('statut', Chambre::STATUT_HORS_SERVICE)->count(),
                'taux_occupation' => $totalChambres > 0 ? round(($chambresOccupees / $totalChambres) * 100, 1) : 0,
            ],
        ]);
    }
}

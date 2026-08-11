<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Liste des services additionnels (petit-déjeuner, parking, spa, navette...).
     * Public : le client doit pouvoir les consulter pour composer sa réservation.
     * ?actifs=1 filtre sur les services activement proposés.
     */
    public function index(Request $request)
    {
        $query = Service::query();

        if ($request->boolean('actifs')) {
            $query->where('est_actif', true);
        }

        return response()->json($query->orderBy('nom')->get());
    }

    public function show(Service $service)
    {
        return response()->json($service);
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->validated());

        return response()->json([
            'message' => 'Service créé avec succès.',
            'data' => $service,
        ], 201);
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->validated());

        return response()->json([
            'message' => 'Service mis à jour avec succès.',
            'data' => $service,
        ]);
    }

    public function destroy(Service $service)
    {
        if ($service->reservations()->exists()) {
            // On désactive plutôt que de supprimer pour préserver l'historique des réservations passées.
            $service->update(['est_actif' => false]);

            return response()->json([
                'message' => 'Ce service est utilisé dans des réservations existantes : il a été désactivé plutôt que supprimé.',
            ]);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé avec succès.']);
    }
}

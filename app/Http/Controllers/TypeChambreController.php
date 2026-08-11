<?php

namespace App\Http\Controllers;

use App\Http\Requests\TypeChambre\StoreTypeChambreRequest;
use App\Http\Requests\TypeChambre\UpdateTypeChambreRequest;
use App\Models\TypeChambre;
use Illuminate\Support\Facades\Storage;

class TypeChambreController extends Controller
{
    /**
     * Liste des types de chambres (Standard, Deluxe, Suite...).
     * Public : utilisé par le catalogue client pour filtrer la recherche.
     */
    public function index()
    {
        return response()->json(TypeChambre::withCount('chambres')->orderBy('prix_de_base')->get());
    }

    public function show(TypeChambre $typeChambre)
    {
        return response()->json($typeChambre->load('chambres'));
    }

    public function store(StoreTypeChambreRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('type_chambres', 'public');
        }

        $typeChambre = TypeChambre::create($data);

        return response()->json([
            'message' => 'Type de chambre créé avec succès.',
            'data' => $typeChambre,
        ], 201);
    }

    public function update(UpdateTypeChambreRequest $request, TypeChambre $typeChambre)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($typeChambre->image) {
                Storage::disk('public')->delete($typeChambre->image);
            }
            $data['image'] = $request->file('image')->store('type_chambres', 'public');
        }

        $typeChambre->update($data);

        return response()->json([
            'message' => 'Type de chambre mis à jour avec succès.',
            'data' => $typeChambre,
        ]);
    }

    public function destroy(TypeChambre $typeChambre)
    {
        if ($typeChambre->chambres()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer : des chambres sont encore rattachées à ce type.',
            ], 422);
        }

        if ($typeChambre->image) {
            Storage::disk('public')->delete($typeChambre->image);
        }

        $typeChambre->delete();

        return response()->json(['message' => 'Type de chambre supprimé avec succès.']);
    }
}

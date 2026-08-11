<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Profil de l'utilisateur connecté (client ou personnel).
     */
    public function show(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();
        $data = $request->safe()->except('password');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'data' => $user->fresh()->load('roles'),
        ]);
    }
}

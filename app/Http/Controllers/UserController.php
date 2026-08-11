<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Gestion des comptes par l'administrateur : liste des clients et du personnel.
     * Route réservée au rôle Administrateur.
     */
    public function index(Request $request)
    {
        $query = User::with('roles')->withCount('reservations');

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    /**
     * Création d'un compte personnel (Réceptionniste ou Administrateur) par l'admin.
     * Pour les clients, on passe par /register (auto-inscription).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'role' => ['required', Rule::in(['Administrateur', 'Receptionniste', 'Client'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user->load('roles'),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'role' => ['sometimes', Rule::in(['Administrateur', 'Receptionniste', 'Client'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $donnees = $request->only(['name', 'email', 'phone', 'address']);

        if ($request->filled('password')) {
            $donnees['password'] = Hash::make($request->input('password'));
        }

        $user->update($donnees);

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => $user->load('roles'),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        if ($user->reservations()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer : cet utilisateur a des réservations liées.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
    }
}

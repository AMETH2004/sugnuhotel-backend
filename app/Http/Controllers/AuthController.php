<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        // 1. Validation des données envoyées depuis Angular
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Création de l'utilisateur dans la base MySQL
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 3. Attribution du rôle "Client" en toute sécurité
        try {
            $user->assignRole('Client');
        } catch (\Exception $e) {
            // Si le rôle Spatie 'Client' n'existe pas encore, l'inscription ne plante pas (pas d'erreur 500)
        }

        // 4. Génération du Token API Sanctum pour Angular
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Envoi de la réponse JSON au frontend Angular
        return response()->json([
            'status'  => true,
            'message' => 'Utilisateur inscrit avec succès',
            'user'    => $user,
            'token'   => $token,
            'role'    => $user->getRoleNames()->first() ?? 'Client'
        ], 201);
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Identifiants incorrects.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Connexion réussie',
            'user'    => $user,
            'token'   => $token,
            'role'    => $user->getRoleNames()->first() ?? 'Client'
        ], 200);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Déconnexion réussie'
        ], 200);
    }
}
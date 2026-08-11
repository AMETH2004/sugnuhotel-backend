<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Routes API - SugnuHotel
|--------------------------------------------------------------------------
*/

// ------------------------------------------------------------------
// 1. Routes Publiques (Inscriptions & Connexions)
// ------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// ------------------------------------------------------------------
// 2. Routes Protégées (Nécessitent un Token Sanctuam valide)
// ------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Récupérer les informations de l'utilisateur connecté avec son rôle
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'role' => $request->user()->getRoleNames()->first(),
        ]);
    });

});
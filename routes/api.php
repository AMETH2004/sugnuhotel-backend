<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TypeChambreController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API - SugnuHotel
|--------------------------------------------------------------------------
*/

// ------------------------------------------------------------------
// 1. Routes Publiques
// ------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Catalogue consultable sans être connecté (parcours "Recherche" avant login).
Route::get('/type-chambres', [TypeChambreController::class, 'index']);
Route::get('/type-chambres/{typeChambre}', [TypeChambreController::class, 'show']);
Route::get('/chambres/disponibles', [ChambreController::class, 'disponibles']);
Route::get('/chambres/{chambre}', [ChambreController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);

// ------------------------------------------------------------------
// 2. Routes Protégées (Nécessitent un token Sanctum valide)
// ------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user()->load('roles'),
            'role' => $request->user()->getRoleNames()->first(),
        ]);
    });

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // -- Réservations : accessibles à tout utilisateur connecté.
    // Le filtrage (mes réservations vs toutes) et les autorisations fines
    // sont gérés dans ReservationController (un client ne voit que les siennes).
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::post('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);

    // ------------------------------------------------------------------
    // 3. Routes Personnel (Réceptionniste + Administrateur)
    // ------------------------------------------------------------------
    Route::middleware('role:Administrateur|Receptionniste')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/chambres', [ChambreController::class, 'index']);
        Route::patch('/chambres/{chambre}/statut', [ChambreController::class, 'changerStatut']);

        Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn']);
        Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut']);
    });

    // ------------------------------------------------------------------
    // 4. Routes Administrateur uniquement
    // ------------------------------------------------------------------
    Route::middleware('role:Administrateur')->group(function () {
        Route::post('/type-chambres', [TypeChambreController::class, 'store']);
        Route::put('/type-chambres/{typeChambre}', [TypeChambreController::class, 'update']);
        Route::delete('/type-chambres/{typeChambre}', [TypeChambreController::class, 'destroy']);

        Route::post('/chambres', [ChambreController::class, 'store']);
        Route::put('/chambres/{chambre}', [ChambreController::class, 'update']);
        Route::delete('/chambres/{chambre}', [ChambreController::class, 'destroy']);
        Route::delete('/chambres/{chambre}/photos/{image}', [ChambreController::class, 'supprimerPhoto']);

        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});

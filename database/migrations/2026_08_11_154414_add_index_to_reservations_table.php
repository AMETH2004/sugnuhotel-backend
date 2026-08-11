<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Index utilisé lors de la vérification de conflit de réservation
     * (recherche des réservations actives d'une chambre sur une période).
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['chambre_id', 'statut', 'date_arrivee', 'date_depart'], 'reservations_conflict_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_conflict_index');
        });
    }
};

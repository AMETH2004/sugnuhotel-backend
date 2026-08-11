<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('reservations', function (Blueprint $table) {
        $table->id();
        $table->string('numero_reservation')->unique();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('chambre_id')->constrained('chambres')->onDelete('cascade');
        $table->date('date_arrivee');
        $table->date('date_depart');
        $table->integer('nombre_adultes');
        $table->integer('nombre_enfants')->default(0);
        $table->decimal('prix_total', 10, 2);
        $table->enum('statut', ['en_attente', 'confirmee', 'enregistree', 'terminee', 'annulee'])->default('en_attente');
        $table->text('demandes_speciales')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

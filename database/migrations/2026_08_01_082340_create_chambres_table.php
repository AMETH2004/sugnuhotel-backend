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
    Schema::create('chambres', function (Blueprint $table) {
        $table->id();
        $table->string('numero_chambre')->unique();
        $table->foreignId('type_chambre_id')->constrained('type_chambres')->onDelete('cascade');
        $table->integer('etage');
        $table->decimal('prix_par_nuit', 10, 2);
        $table->integer('capacite_max');
        $table->enum('statut', ['disponible', 'occupee', 'maintenance', 'hors_service'])->default('disponible');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chambres');
    }
};

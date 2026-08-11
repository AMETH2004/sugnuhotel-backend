<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appel du seeder des rôles et de l'administrateur
        $this->call(RoleEtAdminSeeder::class);

        // Jeu de données de démonstration (comptes de test, chambres, services)
        $this->call(HotelDemoSeeder::class);
    }
}
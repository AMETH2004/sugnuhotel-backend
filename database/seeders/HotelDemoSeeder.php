<?php

namespace Database\Seeders;

use App\Models\Chambre;
use App\Models\Service;
use App\Models\TypeChambre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Jeu de données de démonstration : comptes de test, types de chambres,
 * chambres physiques et services additionnels. Idempotent (firstOrCreate)
 * pour pouvoir être rejoué sans dupliquer les données.
 */
class HotelDemoSeeder extends Seeder
{
    public function run(): void
    {
        // -- Comptes de test pour la réception et un client --------------------
        $reception = User::firstOrCreate(
            ['email' => 'reception@sugnuhotel.com'],
            ['name' => 'Réception SugnuHotel', 'password' => Hash::make('motdepasse123')]
        );
        if (!$reception->hasRole('Receptionniste')) {
            $reception->assignRole('Receptionniste');
        }

        $client = User::firstOrCreate(
            ['email' => 'client@sugnuhotel.com'],
            ['name' => 'Client Démo', 'password' => Hash::make('motdepasse123')]
        );
        if (!$client->hasRole('Client')) {
            $client->assignRole('Client');
        }

        // -- Types de chambres ---------------------------------------------------
        $standard = TypeChambre::firstOrCreate(
            ['nom' => 'Standard'],
            ['description' => 'Chambre confortable avec les équipements essentiels.', 'prix_de_base' => 25000, 'capacite_max' => 2]
        );
        $deluxe = TypeChambre::firstOrCreate(
            ['nom' => 'Deluxe'],
            ['description' => 'Chambre spacieuse avec vue et literie premium.', 'prix_de_base' => 45000, 'capacite_max' => 3]
        );
        $suite = TypeChambre::firstOrCreate(
            ['nom' => 'Suite'],
            ['description' => 'Suite avec salon séparé, idéale pour un long séjour.', 'prix_de_base' => 85000, 'capacite_max' => 4]
        );

        // -- Chambres physiques ---------------------------------------------------
        $chambres = [
            ['numero_chambre' => '101', 'type_chambre_id' => $standard->id, 'etage' => 1, 'prix_par_nuit' => 25000, 'capacite_max' => 2],
            ['numero_chambre' => '102', 'type_chambre_id' => $standard->id, 'etage' => 1, 'prix_par_nuit' => 25000, 'capacite_max' => 2],
            ['numero_chambre' => '103', 'type_chambre_id' => $standard->id, 'etage' => 1, 'prix_par_nuit' => 27000, 'capacite_max' => 2],
            ['numero_chambre' => '201', 'type_chambre_id' => $deluxe->id, 'etage' => 2, 'prix_par_nuit' => 45000, 'capacite_max' => 3],
            ['numero_chambre' => '202', 'type_chambre_id' => $deluxe->id, 'etage' => 2, 'prix_par_nuit' => 45000, 'capacite_max' => 3],
            ['numero_chambre' => '301', 'type_chambre_id' => $suite->id, 'etage' => 3, 'prix_par_nuit' => 85000, 'capacite_max' => 4],
        ];

        foreach ($chambres as $chambre) {
            Chambre::firstOrCreate(['numero_chambre' => $chambre['numero_chambre']], $chambre);
        }

        // -- Services additionnels ---------------------------------------------------
        $services = [
            ['nom' => 'Petit-déjeuner', 'description' => 'Buffet petit-déjeuner par personne et par jour.', 'prix' => 5000],
            ['nom' => 'Parking', 'description' => 'Place de parking sécurisée pour la durée du séjour.', 'prix' => 3000],
            ['nom' => 'Spa', 'description' => "Accès à l'espace bien-être et spa.", 'prix' => 15000],
            ['nom' => 'Navette aéroport', 'description' => 'Transfert aller-retour depuis/vers l\'aéroport.', 'prix' => 10000],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['nom' => $service['nom']], $service + ['est_actif' => true]);
        }
    }
}

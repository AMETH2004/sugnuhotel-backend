<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleEtAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Création des 3 rôles (idempotent : ne recrée pas si déjà présents).
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrateur']);
        Role::firstOrCreate(['name' => 'Receptionniste']);
        Role::firstOrCreate(['name' => 'Client']);

        // Compte Admin de test.
        $admin = User::firstOrCreate(
            ['email' => 'admin@sugnuhotel.com'],
            [
                'name' => 'Admin SugnuHotel',
                'password' => Hash::make('motdepasse123'),
            ]
        );

        if (!$admin->hasRole($roleAdmin)) {
            $admin->assignRole($roleAdmin);
        }
    }
}

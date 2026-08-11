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
        // Création des 3 rôles
        $roleAdmin = Role::create(['name' => 'Administrateur']);
        $roleReceptionniste = Role::create(['name' => 'Receptionniste']);
        $roleClient = Role::create(['name' => 'Client']);

        // Création du compte Admin de test
        $admin = User::create([
            'name' => 'Admin SugnuHotel',
            'email' => 'admin@sugnuhotel.com',
            'password' => Hash::make('motdepasse123'),
        ]);
        
        $admin->assignRole($roleAdmin);
    }
}
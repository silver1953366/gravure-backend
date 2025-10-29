<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Optionnel : Désactiver/réactiver la vérification des clés étrangères et truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Utilisateur ID 1 : Administrateur (Accès complet)
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin EMES',
            'email' => 'admin@emes.com', 
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '070000000',
            'address' => 'Bureau Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Utilisateur ID 2 : Contrôleur/Opérateur (Accès aux commandes/devis de production)
        DB::table('users')->insert([
            'id' => 2,
            'name' => 'Controller Test',
            'email' => 'controller@emes.com', 
            'password' => Hash::make('password'),
            'role' => 'controller',
            'phone' => '071111111',
            'address' => 'Atelier Production',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 3. Utilisateur ID 3 : Client A (Utilisé pour les scénarios principaux)
        DB::table('users')->insert([
            'id' => 3,
            'name' => 'Client A (Principal)',
            'email' => 'client@test.com', 
            'password' => Hash::make('password'),
            'role' => 'client',
            'phone' => '072222222',
            'address' => 'Résidence Client A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Utilisateur ID 4 : Client B (Pour tester l'accès aux devis/commandes d'autrui)
        DB::table('users')->insert([
            'id' => 4,
            'name' => 'Client B (Secondaire)',
            'email' => 'client2@test.com', 
            'password' => Hash::make('password'),
            'role' => 'client',
            'phone' => '073333333',
            'address' => 'Résidence Client B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

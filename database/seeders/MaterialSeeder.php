<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Désactiver temporairement les vérifications de clés étrangères pour MySQL/MariaDB
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Truncate (vide la table)
        DB::table('materials')->truncate();
        
        $materials = [
            // Matériau 1 : LAITON (Correspond à l'ancien 'Laiton Poli (Effet Or)')
            [
                'category_id' => 2, // Signalétique
                'name' => 'Laiton', 
                'slug' => 'laiton-standard',
                'description' => 'Métal lourd et brillant, utilisé pour les plaques de prestige.', 
                'image_url' => '/images/materials/laiton.jpg',
                'color' => '#C4B454', // Or
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], 
            // Matériau 2 : ALUMINIUM (Correspond à l'ancien 'Aluminium Anodisé')
            [
                'category_id' => 2, // Signalétique
                'name' => 'Aluminium', 
                'slug' => 'aluminium-standard',
                'description' => 'Métal léger, résistant à la corrosion. Finition mate.', 
                'image_url' => '/images/materials/aluminium.jpg',
                'color' => '#CCCCCC', // Gris alu
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Matériau 3 : ALUMINIUM COBALT (Sublimable)
            [
                'category_id' => 3, // Personnel
                'name' => 'Aluminium Cobalt', 
                'slug' => 'aluminium-cobalt',
                'description' => 'Plaques d\'aluminium traitées pour la personnalisation couleur/photo.', 
                'image_url' => '/images/materials/alu_cobalt.jpg',
                'color' => '#FFFFFF',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], 
            // Matériau 4 : GRANIT (Correspond à l'ancien 'Granit Noir Fin')
            [
                'category_id' => 1, // Funéraire
                'name' => 'Granit', 
                'slug' => 'granit-noir',
                'description' => 'Pierre naturelle polie, finition sobre et durable pour usages funéraires.', 
                'image_url' => '/images/materials/granit.jpg',
                'color' => '#1A1A1A', // Noir
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Matériau 5 : ACRYLIQUE (PMMA)
            [
                'category_id' => 2, // Signalétique
                'name' => 'Acrylique (PMMA)', 
                'slug' => 'acrylique-pmma',
                'description' => 'Plastique économique pour la gravure laser, offrant un contraste de couleur.', 
                'image_url' => '/images/materials/acrylique.jpg',
                'color' => '#000000',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('materials')->insert($materials);
        
        // 3. Réactiver les vérifications de clé étrangère
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

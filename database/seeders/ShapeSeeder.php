<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShapeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shapes = [
            // Forme 1 : Plaque de base (la plus courante)
            [
                'name' => 'Plaque Rectangle Standard', 
                'slug' => 'plaque-standard',
                'image_url' => '/images/shapes/plaque_standard.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Forme 2 : Forme funéraire
            [
                'name' => 'Livre (Ouvert)', 
                'slug' => 'livre-ouvert',
                'image_url' => '/images/shapes/livre_ouvert.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Forme 3 : Forme funéraire ou cadeau
            [
                'name' => 'Cœur', 
                'slug' => 'coeur',
                'image_url' => '/images/shapes/coeur.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Forme 4 : Produit personnel (dimension très fixe)
            [
                'name' => 'Pin\'s / Badge Rond', 
                'slug' => 'pins-badge-rond',
                'image_url' => '/images/shapes/pins_rond.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Forme 5 : Grande structure
            [
                'name' => 'Pierre Tombale Classique', 
                'slug' => 'pierre-tombale-classique',
                'image_url' => '/images/shapes/pierre_tombale.png',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('shapes')->insert($shapes);
    }
}
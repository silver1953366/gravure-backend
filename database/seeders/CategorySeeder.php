<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Funéraire', 
                'description' => 'Produits destinés au domaine funéraire (plaques tombales, ornements).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Signalétique', 
                'description' => 'Plaques d\'indication, de porte, de bureau, et panneaux de signalisation.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Personnel', 
                'description' => 'Produits pour usage personnel (badges, pin\'s, objets personnalisés).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('categories')->insert($categories);
    }
}

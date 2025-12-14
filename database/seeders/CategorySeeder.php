<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // 💡 AJOUT : Pour générer les slugs

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => 1, // 💡 Assignation explicite de l'ID 1
                'name' => 'Funéraire', 
                'slug' => Str::slug('Funéraire'), // "funeraire"
                'description' => 'Produits destinés au domaine funéraire (plaques tombales, ornements).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2, // 💡 Assignation explicite de l'ID 2
                'name' => 'Signalétique', 
                'slug' => Str::slug('Signalétique'), // "signaletique"
                'description' => 'Plaques d\'indication, de porte, de bureau, et panneaux de signalisation.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3, // 💡 Assignation explicite de l'ID 3
                'name' => 'Personnel', 
                'slug' => Str::slug('Personnel'), // "personnel"
                'description' => 'Produits pour usage personnel (badges, pin\'s, objets personnalisés).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // ⚠️ Utiliser `insertOrIgnore` pour éviter les erreurs si les IDs existent déjà.
        // On insère l'ID, le nom, le slug, etc.
        DB::table('categories')->insertOrIgnore($categories);
    }
}
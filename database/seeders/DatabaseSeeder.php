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
        $this->call([
            // --- 1. Entités de base (Aucune dépendance) ---
            UserSeeder::class,      // Utilisateurs
            CategorySeeder::class,  // Catégories (Funéraire, Signalétique, Personnel)
            MaterialSeeder::class,  // Matériaux (Laiton, Aluminium, Granit, Acrylique)
            ShapeSeeder::class,     // Formes (Rectangle, Ovale, Cercle)
            
            // --- 2. Entités de Catalogue (Dépendent des bases) ---
            MaterialDimensionSeeder::class, // Dimensions spécifiques aux matériaux
            DiscountSeeder::class,          // Règles de réduction
            
            // --- 3. Entités d'Inventaire (Dépendent du catalogue) ---
            InventorySeeder::class, // Stock basé sur les dimensions des matériaux
            
            // --- 4. Entités Transactionnelles (Dépendent de tout ce qui précède) ---
            QuoteSeeder::class,     // Création des devis (dépend des Users, catalogue, et discounts)
            OrderSeeder::class,     // Création des commandes (dépend des Quotes)
            NotificationSeeder::class //notifications
        ]);
    }
}

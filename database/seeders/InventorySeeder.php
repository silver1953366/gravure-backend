<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\MaterialDimension;
use Carbon\Carbon;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On récupère tous les IDs de MaterialDimension
        $materialDimensions = MaterialDimension::pluck('id');
        
        foreach ($materialDimensions as $id) {
            Inventory::create([
                'material_dimension_id' => $id,
                'stock_quantity' => rand(100, 500), // Stock initial aléatoire
                'reserved_quantity' => rand(10, 50), // Quantité déjà réservée
                'minimum_threshold' => 50, // Seuil d'alerte
                'last_restock_at' => Carbon::now()->subDays(rand(1, 60)),
            ]);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\MaterialDimension;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Nettoyage de la table pour éviter les doublons avec le unique(material_dimension_id)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Inventory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Récupération de toutes les dimensions du catalogue
        $materialDimensions = MaterialDimension::all();

        if ($materialDimensions->isEmpty()) {
            $this->command->warn("Aucune MaterialDimension trouvée. Lancez d'abord MaterialSeeder.");
            return;
        }

        foreach ($materialDimensions as $md) {
            
            // 3. LOGIQUE RÉELLE : Calculer la somme des réservations
            // On additionne la quantité de toutes les commandes liées à cette dimension
            $reservedAmount = Order::where('material_dimension_id', $md->id)->sum('quantity');

            // 4. Calcul du stock physique
            // On génère un stock de base aléatoire (ex: entre 50 et 200) 
            // auquel on ajoute obligatoirement le réservé pour ne pas être en négatif virtuel
            $randomPhysicalStock = rand(50, 200);
            $totalStockQuantity = $randomPhysicalStock + $reservedAmount;

            // 5. Création de l'entrée d'inventaire
            Inventory::create([
                'material_dimension_id' => $md->id,
                'stock_quantity'        => $totalStockQuantity,
                'reserved_quantity'     => $reservedAmount, // Somme réelle des commandes
                'minimum_threshold'     => 50,              // Seuil d'alerte par défaut
                'price_per_unit'        => $md->unit_price_fcfa, // Prix synchronisé au catalogue
                'last_restock_at'       => Carbon::now()->subDays(rand(1, 30)),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }

        $this->command->info("Inventaire initialisé avec succès en respectant les réservations des commandes.");
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quote;
use App\Models\User;
use App\Models\MaterialDimension;
use App\Models\Order;
use Illuminate\Support\Facades\DB; // Import ajouté

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- SÉCURITÉ : Désactiver la vérification des clés étrangères pour le truncate ---
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('quotes')->truncate();
        // Optionnel mais recommandé pour les tests d'intégrité de la commande :
        DB::table('orders')->truncate(); 

        // 1. Récupération des utilisateurs et des dimensions
        // IDs : 3 (Client A), 4 (Client B)
        $clientA = User::find(3); 
        $clientB = User::find(4);
        $md = MaterialDimension::first(); 
        
        if (!$clientA || !$clientB || !$md) {
            echo "Attention : Assurez-vous que UserSeeder et MaterialDimensionSeeder ont été exécutés et contiennent les IDs 3 et 4.\n";
            // Réactiver les clés avant de quitter
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return;
        }

        // --- Devis pour le Client A (ID 3) ---

        // 1. Devis 'draft' (modifiable)
        Quote::create([
            'reference' => 'DEV-A-DRAFT',
            'user_id' => $clientA->id,
            'client_details' => ['name' => $clientA->name, 'email' => $clientA->email, 'phone' => '072222222'],
            'material_dimension_id' => $md->id,
            'material_id' => $md->material_id,
            'shape_id' => $md->shape_id,
            'quantity' => 10,
            'unit_price_fcfa' => $md->unit_price_fcfa,
            'price_source' => 'standard',
            'dimension_label' => $md->dimension_label,
            'base_price_fcfa' => $md->unit_price_fcfa * 10,
            'final_price_fcfa' => $md->unit_price_fcfa * 10,
            'status' => Quote::STATUS_DRAFT, // Utiliser la constante
            'details_snapshot' => ['creation' => 'Brouillon client A'],
        ]);

        // 2. Devis 'calculated' (prêt à être commandé)
        $calculatedPrice = 250000.00;
        $quoteCalculatedA = Quote::create([
            'reference' => 'DEV-A-CALCULATED',
            'user_id' => $clientA->id,
            'client_details' => ['name' => $clientA->name, 'email' => $clientA->email, 'phone' => '072222222'],
            'material_dimension_id' => $md->id,
            'material_id' => $md->material_id,
            'shape_id' => $md->shape_id,
            'quantity' => 5,
            'unit_price_fcfa' => $md->unit_price_fcfa,
            'price_source' => 'standard',
            'dimension_label' => $md->dimension_label,
            'base_price_fcfa' => $calculatedPrice,
            'final_price_fcfa' => $calculatedPrice,
            'status' => Quote::STATUS_CALCULATED, // Prêt à convertir
            'details_snapshot' => ['admin_override' => false],
        ]);

        // 3. Devis 'ordered' (déjà converti) -> Nécessite de créer la commande correspondante
        $orderedPrice = 500000.00;
        $quoteOrderedA = Quote::create([
            'reference' => 'DEV-A-ORDERED',
            'user_id' => $clientA->id,
            'client_details' => ['name' => $clientA->name, 'email' => $clientA->email, 'phone' => '072222222'],
            'material_dimension_id' => $md->id,
            'material_id' => $md->material_id,
            'shape_id' => $md->shape_id,
            'quantity' => 2,
            'unit_price_fcfa' => $md->unit_price_fcfa,
            'price_source' => 'standard',
            'dimension_label' => $md->dimension_label,
            'base_price_fcfa' => $orderedPrice,
            'final_price_fcfa' => $orderedPrice,
            'status' => Quote::STATUS_ORDERED, // Statut final
            'details_snapshot' => ['creation' => 'Commande déjà passée'],
        ]);

        // --- Crée la Commande liée pour le test d'intégrité ---
        $order = Order::create([
            'user_id' => $clientA->id,
            'quote_id' => $quoteOrderedA->id, // Liaison bidirectionnelle
            'reference' => 'CMD-A-0001',
            'final_price_fcfa' => $orderedPrice,
            'shipping_address' => ['street' => '1 rue de la Commande', 'city' => 'Libreville', 'postal_code' => '00000'],
            'status' => Order::STATUS_PENDING_PAYMENT,
            'material_id' => $quoteOrderedA->material_id,
            'shape_id' => $quoteOrderedA->shape_id,
            'material_dimension_id' => $quoteOrderedA->material_dimension_id,
            'quantity' => $quoteOrderedA->quantity,
            'client_details' => $quoteOrderedA->client_details,
            'details_snapshot' => $quoteOrderedA->details_snapshot,
        ]);
        // Mettre à jour l'order_id dans le devis après la création de la commande
        $quoteOrderedA->update(['order_id' => $order->id]);
        
        // --- Devis pour le Client B (ID 4) ---

        // 4. Devis 'calculated' pour Client B (pour tester l'accès illégal par Client A)
        $calculatedPriceB = 300000.00;
        Quote::create([
            'reference' => 'DEV-B-CALCULATED',
            'user_id' => $clientB->id,
            'client_details' => ['name' => $clientB->name, 'email' => $clientB->email, 'phone' => '073333333'],
            'material_dimension_id' => $md->id,
            'material_id' => $md->material_id,
            'shape_id' => $md->shape_id,
            'quantity' => 1,
            'unit_price_fcfa' => $md->unit_price_fcfa,
            'price_source' => 'standard',
            'dimension_label' => $md->dimension_label,
            'base_price_fcfa' => $calculatedPriceB,
            'final_price_fcfa' => $calculatedPriceB,
            'status' => Quote::STATUS_CALCULATED,
            'details_snapshot' => ['creation' => 'Devis client B'],
        ]);

        // --- SÉCURITÉ : Réactiver la vérification des clés étrangères ---
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

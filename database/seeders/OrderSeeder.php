<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Quote;
// Assurez-vous que le modèle Quote est bien accessible et défini

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. On cherche un devis qui est au statut 'calculated' et qui n'a PAS de commande associée.
        // Utilisation de whereDoesntHave pour s'assurer qu'aucune relation 'order' n'existe, 
        // ce qui est plus robuste si vous n'avez pas de colonne 'order_id' dans la table quotes.
        // Si la colonne 'order_id' existe dans la table quotes, la condition initiale est correcte.
        // Je maintiens la condition initiale ('whereNull('order_id')') car elle était présente.

        $calculatedQuote = Quote::where('status', Quote::STATUS_CALCULATED)
                                ->whereNull('order_id') // Vérifie que ce devis n'a pas encore été lié à une commande
                                ->first();

        if (!$calculatedQuote) {
            $this->command->info("Attention : Aucun devis CALCULATED non commandé n'a été trouvé. Vérifiez QuoteSeeder. Aucune commande créée.");
            return;
        }

        // --- 2. Préparation des données de commande (COPIE DU DEVIS) ---
        $orderData = [
            'user_id'                 => $calculatedQuote->user_id,
            'quote_id'                => $calculatedQuote->id,
            // Génération d'une référence unique
            'reference'               => 'CMD-TEST-' . str_pad($calculatedQuote->id, 4, '0', STR_PAD_LEFT) . '-' . time(), 
            'final_price_fcfa'        => $calculatedQuote->final_price_fcfa,
            
            // Copie des spécifications produit (Snapshot)
            'material_id'             => $calculatedQuote->material_id,
            'shape_id'                => $calculatedQuote->shape_id,
            'material_dimension_id'   => $calculatedQuote->material_dimension_id,
            'quantity'                => $calculatedQuote->quantity,
            // Les champs JSON sont copiés directement (ils devraient déjà être des tableaux/objets PHP si castés)
            'client_details'          => $calculatedQuote->client_details,
            'details_snapshot'        => $calculatedQuote->details_snapshot,

            // Données spécifiques à la commande (simule une commande passée et payée)
            'status' => Order::STATUS_PAID, 
            'payment_id' => 'PAY-TEST-' . uniqid(), // Utilisation de uniqid() pour une meilleure unicité de test
            
            // Adresse de livraison figée au moment de la commande
            'shipping_address' => [ 
                'street' => '456 Avenue des Tests',
                'city' => 'Douala',
                'country' => 'Cameroun',
                'zip_code' => '1010',
            ],
        ];

        // 3. Création de la commande
        $order = Order::create($orderData);
        
        // 4. Mettre à jour le devis pour refléter qu'il est commandé
        // Note : Cette étape suppose que le modèle Quote a un champ 'order_id' pour la relation One-to-One
        $calculatedQuote->update([
            'status' => Quote::STATUS_ORDERED, 
            'order_id' => $order->id
        ]);
        
        $this->command->info("Commande test payée créée avec succès (ID: {$order->id}, ID Quote mis à jour: {$calculatedQuote->id}).");
    }
}

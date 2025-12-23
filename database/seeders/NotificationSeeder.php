<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Quote;
use App\Models\Order;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Récupérer des utilisateurs pour le test (un client et un admin)
        $client = User::where('role', User::ROLE_CLIENT)->first();
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        if (!$client) {
            $this->command->warn("Aucun client trouvé pour le seeder de notifications.");
            return;
        }

        // 2. Notifications simples (système) pour le client
        Notification::create([
            'user_id' => $client->id,
            'type' => Notification::TYPE_INFO,
            'title' => 'Bienvenue !',
            'message' => 'Bienvenue sur votre espace client. Vous pouvez désormais demander des devis.',
            'is_read' => true,
        ]);

        Notification::create([
            'user_id' => $client->id,
            'type' => Notification::TYPE_WARNING,
            'title' => 'Profil incomplet',
            'message' => 'Pensez à ajouter votre adresse de livraison dans votre profil.',
            'is_read' => false,
        ]);

        // 3. Notification liée à un Devis (si un devis existe)
        $quote = Quote::where('user_id', $client->id)->first();
        if ($quote) {
            Notification::create([
                'user_id' => $client->id,
                'type' => Notification::TYPE_SUCCESS,
                'title' => 'Devis calculé',
                'message' => "Le prix pour votre devis {$quote->reference} est disponible.",
                'link' => "/client/quotes/{$quote->id}",
                'resource_id' => $quote->id,
                'resource_type' => Quote::class,
                'is_read' => false,
            ]);
        }

        // 4. Notification liée à une Commande (si une commande existe)
        $order = Order::where('user_id', $client->id)->first();
        if ($order) {
            Notification::create([
                'user_id' => $client->id,
                'type' => Notification::TYPE_INFO,
                'title' => 'Commande expédiée',
                'message' => "Bonne nouvelle ! Votre commande {$order->reference} est en route.",
                'link' => "/client/orders/{$order->id}",
                'resource_id' => $order->id,
                'resource_type' => Order::class,
                'is_read' => false,
            ]);
        }

        // 5. Notification pour l'Admin (si il existe)
        if ($admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => Notification::TYPE_ERROR,
                'title' => 'Alerte Stock',
                'message' => 'Le stock de plaques Aluminium 2mm est critique.',
                'link' => '/admin/inventory',
                'is_read' => false,
            ]);
        }

        $this->command->info('NotificationSeeder exécuté avec succès.');
    }
}
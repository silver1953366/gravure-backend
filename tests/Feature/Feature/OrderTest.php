<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_order_can_be_created_and_persisted(): void
    {
        // 1. Créer une Order
        $order = Order::factory()->create([
            'total_amount' => 150.75, 
            'status' => 'processing',
        ]);

        // 2. Vérifier que l'enregistrement existe en base de données
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => 150.75,
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user_and_a_quote(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['user_id' => $user->id]);

        // Créer la commande en liant les IDs
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'quote_id' => $quote->id,
        ]);

        // 2. Vérifier la relation avec l'utilisateur
        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
        
        // 3. Vérifier la relation avec le devis
        $this->assertInstanceOf(Quote::class, $order->quote);
        $this->assertEquals($quote->id, $order->quote->id);
    }

    /** @test */
    public function it_has_a_unique_order_number(): void
    {
        // 1. Créer une première commande avec un numéro spécifique
        $orderNumber = 'ORD-2024-001';
        Order::factory()->create(['order_number' => $orderNumber]);

        // 2. Tenter de créer une commande avec le même numéro (doit échouer)
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation'); // Laravel utilise souvent ce message pour les violations d'unicité
        
        Order::factory()->create(['order_number' => $orderNumber]);
    }
    
    /** @test */
    public function it_casts_the_shipping_address_field_to_an_array(): void
    {
        // 1. Arrange
        $order = Order::factory()->create();
        
        // 2. Assert
        // Vérifier que l'attribut est casté en tableau (ou objet, si défini ainsi dans le modèle)
        $this->assertIsArray($order->shipping_address);
        $this->assertArrayHasKey('city', $order->shipping_address);
        $this->assertArrayHasKey('postal_code', $order->shipping_address);
        
        // S'assurer que les données JSON existent en base
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            // Utilisation de la syntaxe JSON -> pour vérifier une clé interne
            'shipping_address->country' => $order->shipping_address['country'], 
        ]);
    }

    /** @test */
    public function the_completed_at_field_is_set_only_when_status_is_completed(): void
    {
        // 1. Commander complétée (completed() state)
        $completedOrder = Order::factory()->completed()->create();
        
        // 2. Créer une commande en attente (état par défaut)
        $pendingOrder = Order::factory()->create(['status' => 'pending_payment']);

        // 3. Vérifier les assertions
        $this->assertNotNull($completedOrder->completed_at);
        $this->assertEquals('completed', $completedOrder->status);
        $this->assertInstanceOf(\DateTime::class, $completedOrder->completed_at);
        
        $this->assertNull($pendingOrder->completed_at);
        $this->assertEquals('pending_payment', $pendingOrder->status);
    }
    
    /** @test */
    public function deleting_a_user_cascades_and_deletes_their_orders(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);

        // 2. Act (Supprimer l'utilisateur)
        $user->delete();

        // 3. Assert (Vérifier que la commande est supprimée)
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Material;
use App\Models\MaterialDimension;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Shape;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_quote_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act: Crée un devis avec un prix final spécifique
        $quote = Quote::factory()->create([
            'final_price_fcfa' => 123456.78,
            'status' => 'sent',
        ]);

        // 2. Assert (Vérifier qu'il existe dans la base de données)
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'reference' => $quote->reference, // Vérifie la référence générée
            'final_price_fcfa' => 123456.78,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $quote->user);
        $this->assertEquals($user->id, $quote->user->id);
    }
    
    /** @test */
    public function it_has_a_unique_reference(): void
    {
        // Le factory utilise une séquence statique, forçons la référence ici pour tester l'unicité DB
        $reference = 'QTE-2025-001'; 
        Quote::factory()->create(['reference' => $reference]);

        // 2. Act & Assert: Tenter de créer un devis avec la même référence doit échouer
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');
        Quote::factory()->create(['reference' => $reference]);
    }

    /** @test */
    public function deleting_a_user_cascades_and_deletes_their_quotes(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('quotes', ['id' => $quote->id]);

        $user->delete();

        $this->assertDatabaseMissing('quotes', ['id' => $quote->id]);
    }
    
    /** @test */
    public function it_has_all_necessary_relationships(): void
    {
        // Crée une instance de devis avec des relations spécifiques
        $quote = Quote::factory()->ordered()->create([ // Utilise ordered() pour garantir la relation Order
            'discount_id' => Discount::factory(),
            'material_dimension_id' => MaterialDimension::factory(), // Force la dimension non-nulle
        ]);

        // Vérification des relations
        $this->assertInstanceOf(Material::class, $quote->material);
        $this->assertInstanceOf(Shape::class, $quote->shape);
        $this->assertInstanceOf(MaterialDimension::class, $quote->materialDimension);
        $this->assertInstanceOf(Discount::class, $quote->discount);
        $this->assertInstanceOf(Order::class, $quote->order); 
    }

    /** @test */
    public function it_correctly_casts_json_fields(): void
    {
        $clientDetails = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $detailsSnapshot = ['color_code' => '#FF0000', 'font' => 'Arial', 'text_content' => 'Test Content'];

        $quote = Quote::factory()->create([
            'client_details' => $clientDetails,
            'details_snapshot' => $detailsSnapshot,
        ]);

        // Assure que les attributs sont des tableaux PHP (car ils sont castés)
        $this->assertIsArray($quote->client_details);
        $this->assertIsArray($quote->details_snapshot);

        // Vérifie l'accès aux données internes
        $this->assertEquals('John Doe', $quote->client_details['name']);
        $this->assertEquals('#FF0000', $quote->details_snapshot['color_code']);
    }

    /** @test */
    public function it_calculates_final_price_correctly_with_discount_snapshot(): void
    {
        // Simule un devis pour tester la formule de tarification
        $unitPrice = 100000.00;
        $quantity = 3;
        $discountAmount = 15000.00; 

        $quote = Quote::factory()->create([
            'unit_price_fcfa' => $unitPrice,
            'quantity' => $quantity,
            'base_price_fcfa' => 300000.00, 
            'discount_amount_fcfa' => $discountAmount,
            'final_price_fcfa' => 285000.00, 
        ]);

        // Vérifie la cohérence du prix final calculé
        $expectedFinalPrice = 285000.00;
        $this->assertEquals($expectedFinalPrice, $quote->final_price_fcfa);
        
        // S'assurer que le prix final est bien la soustraction des snapshots
        $this->assertEquals(
            $quote->base_price_fcfa - $quote->discount_amount_fcfa,
            $quote->final_price_fcfa
        );
    }

    /** @test */
    public function a_quote_can_be_created_in_draft_state_using_factory_state(): void
    {
        $quote = Quote::factory()->draft()->create();
        $this->assertEquals('draft', $quote->status);
    }

    /** @test */
    public function a_quote_in_ordered_state_is_linked_to_an_order_and_status_is_set(): void
    {
        // Utilise l'état 'ordered' de la factory, qui crée l'Order
        $quote = Quote::factory()->ordered()->create();
        
        // Assertions sur le statut
        $this->assertEquals('ordered', $quote->status);

        // Assertions sur la relation
        $this->assertNotNull($quote->order_id);
        $this->assertInstanceOf(Order::class, $quote->order);
        $this->assertEquals($quote->order_id, $quote->order->id);
    }
}

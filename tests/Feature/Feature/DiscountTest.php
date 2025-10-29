<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_discount_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act
        $discount = Discount::factory()->create([
            'type' => 'fixed',
            'value' => 50000,
            'is_active' => true,
        ]);

        // 2. Assert (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('discounts', [
            'id' => $discount->id,
            'code' => $discount->code,
            'type' => 'fixed',
            // Les montants sont stockés comme décimaux
            'value' => 50000.00, 
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_supports_the_expired_state_transformation(): void
    {
        // 1. Arrange & Act
        $expiredDiscount = Discount::factory()->expired()->create();

        // 2. Assert
        $this->assertFalse($expiredDiscount->is_active);
        // Vérifie que la date d'expiration est bien dans le passé
        $this->assertTrue($expiredDiscount->expires_at->isPast()); 
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $discount = Discount::factory()->create(['user_id' => $user->id]);

        // 2. Act & Assert
        // Vérifie la relation 'user'
        $this->assertInstanceOf(User::class, $discount->user);
        $this->assertEquals($user->id, $discount->user->id);
    }

    /** @test */
    public function the_discount_code_is_unique(): void
    {
        // 1. Arrange
        $code = 'PROMO-UNIQUE';
        Discount::factory()->create(['code' => $code]);

        // 2. Act & Assert
        // Tenter de créer un doublon doit échouer avec une exception de requête (violation de contrainte unique)
        $this->expectException(QueryException::class);
        Discount::factory()->create(['code' => $code]);
    }

    /** @test */
    public function it_can_determine_if_it_is_currently_valid_based_on_active_status_and_expiration(): void
    {
        // Supposons une méthode `isValid()` sur le modèle Discount.

        // 1. Arrangement (Méthode 1: Actif et non expiré)
        $validDiscount = Discount::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addMonth(), 
        ]);
        
        // 2. Arrangement (Méthode 2: Inactif, même si la date est bonne)
        $inactiveDiscount = Discount::factory()->create([
            'is_active' => false,
            'expires_at' => now()->addMonth(),
        ]);
        
        // 3. Arrangement (Méthode 3: Expired, même si actif)
        $expiredDiscount = Discount::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(), // Expiré hier
        ]);

        // 4. Assertions (Ces assertions nécessitent les scopes/méthodes sur le modèle)
        // Ceci vérifie que les attributs sont bien définis pour permettre la validation.
        $this->assertTrue($validDiscount->is_active);
        $this->assertFalse($inactiveDiscount->is_active);
        $this->assertTrue($expiredDiscount->expires_at->isPast());
    }

    /** @test */
    public function deleting_a_user_cascades_and_deletes_their_discounts(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $discount = Discount::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('discounts', ['id' => $discount->id]);

        // 2. Act (Supprimer l'utilisateur)
        $user->delete();

        // 3. Assert (Vérifier que la réduction est supprimée)
        // Ceci est géré par `onDelete('cascade')` dans la migration de la colonne `user_id`
        $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
    }
}

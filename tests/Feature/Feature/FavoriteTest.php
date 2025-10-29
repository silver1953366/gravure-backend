<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_favorite_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act
        $favorite = Favorite::factory()->create();

        // 2. Assert (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('favorites', [
            'id' => $favorite->id,
            'user_id' => $favorite->user_id,
            'favoritable_id' => $favorite->favoritable_id,
            'favoritable_type' => $favorite->favoritable_type,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $favorite = Favorite::factory()->create(['user_id' => $user->id]);

        // 2. Act & Assert
        // Vérifie la relation 'user'
        $this->assertInstanceOf(User::class, $favorite->user);
        $this->assertEquals($user->id, $favorite->user->id);
    }

    /** @test */
    public function it_can_track_a_polymorphic_favoritable_model(): void
    {
        // 1. Arrange
        $material = Material::factory()->create();
        
        // On crée le favori lié au Material
        $favorite = Favorite::factory()->create([
            'favoritable_id' => $material->id,
            'favoritable_type' => Material::class,
        ]);

        // 2. Act & Assert
        // Vérifie la relation polymorphique (favoritable)
        $this->assertInstanceOf(Material::class, $favorite->favoritable);
        $this->assertEquals($material->id, $favorite->favoritable->id);
    }

    /** @test */
    public function deleting_a_user_cascades_and_deletes_their_favorites(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $favorite = Favorite::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('favorites', ['id' => $favorite->id]);

        // 2. Act (Supprimer l'utilisateur)
        $user->delete();

        // 3. Assert (Vérifier que le favori est supprimé)
        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
    }

    /** @test */
    public function deleting_the_favoritable_model_cascades_and_deletes_the_favorite_entry(): void
    {
        // 1. Arrange
        $material = Material::factory()->create();
        $favorite = Favorite::factory()->create([
            'favoritable_id' => $material->id,
            'favoritable_type' => Material::class,
        ]);

        $this->assertDatabaseHas('favorites', ['id' => $favorite->id]);

        // 2. Act (Supprimer le modèle Material)
        $material->delete();

        // 3. Assert (Vérifier que l'entrée Favorite est supprimée)
        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
    }

    /** @test */
    public function a_user_cannot_favorite_the_same_item_twice(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $material = Material::factory()->create();

        $attributes = [
            'user_id' => $user->id,
            'favoritable_id' => $material->id,
            'favoritable_type' => Material::class,
        ];

        // Crée le premier favori
        Favorite::factory()->create($attributes);
        
        // 2. Act & Assert
        // Tenter de créer un doublon devrait générer une QueryException 
        // si une contrainte d'unicité est définie sur [user_id, favoritable_id, favoritable_type]
        $this->expectException(QueryException::class);
        
        Favorite::factory()->create($attributes);
    }
}

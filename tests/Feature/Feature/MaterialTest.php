<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_material_can_be_created_and_persisted()
    {
        // Créer un Material avec des valeurs spécifiques
        $material = Material::factory()->create([
            'price' => 42.99,
            'stock_quantity' => 10,
        ]);

        // Vérifier que l'enregistrement existe en base de données
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'sku' => $material->sku,
            'price' => 42.99,
            'stock_quantity' => 10,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user_and_a_category()
    {
        // Créer les dépendances
        $user = User::factory()->create();
        $category = Category::factory()->create();
        
        // Créer le Material en liant les IDs
        $material = Material::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Vérifier la relation avec l'utilisateur
        $this->assertInstanceOf(User::class, $material->user);
        $this->assertEquals($user->id, $material->user->id);

        // Vérifier la relation avec la catégorie
        $this->assertInstanceOf(Category::class, $material->category);
        $this->assertEquals($category->id, $material->category->id);
    }

    /** @test */
    public function its_stock_can_be_updated_by_inventory_movements()
    {
        // Initialiser le stock à 10
        $material = Material::factory()->create(['stock_quantity' => 10]);

        // Augmenter le stock de 5
        $material->stock_quantity += 5;
        $material->save();
        $material->refresh();
        
        // Vérifier le nouveau stock
        $this->assertEquals(15, $material->stock_quantity);
    }
}

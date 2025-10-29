<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_category_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act
        $category = Category::factory()->create(['name' => 'Équipement lourd']);

        // 2. Assert (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Équipement lourd',
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_supports_the_inactive_state_transformation(): void
    {
        // 1. Arrange & Act
        $inactiveCategory = Category::factory()->inactive()->create();

        // 2. Assert
        $this->assertFalse($inactiveCategory->is_active);
        $this->assertDatabaseHas('categories', [
            'id' => $inactiveCategory->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_supports_a_hierarchical_structure_parent_and_children(): void
    {
        // 1. Arrange
        $parent = Category::factory()->create(['name' => 'Produits finis']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'Câbles']);
        $grandchild = Category::factory()->childOf($child)->create(['name' => 'Câbles électriques']);

        // 2. Act & Assert
        // Vérifie la relation Parent (BelongsTo)
        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
        
        // Vérifie la relation Children (HasMany)
        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->children->contains($grandchild));
        $this->assertCount(1, $parent->children);
        $this->assertCount(1, $child->children);
    }

    /** @test */
    public function it_can_have_multiple_materials(): void
    {
        // 1. Arrange
        $category = Category::factory()->create();
        Material::factory()->count(5)->create(['category_id' => $category->id]);

        // 2. Act & Assert
        $this->assertCount(5, $category->materials);
        $this->assertInstanceOf(Material::class, $category->materials->first());
        $this->assertEquals($category->id, $category->materials->first()->category_id);
    }

    /** @test */
    public function deleting_a_category_cascades_and_deletes_related_materials_and_resets_children_parent_id(): void
    {
        // 1. Arrange
        $parent = Category::factory()->create(['name' => 'Parent']);
        // Enfant lié au parent
        $child = Category::factory()->childOf($parent)->create(['name' => 'Enfant']); 
        // Matériau lié au parent
        $material = Material::factory()->create(['category_id' => $parent->id]);

        $this->assertDatabaseHas('materials', ['id' => $material->id]);
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => $parent->id]);

        // 2. Act (Supprimer la catégorie parente)
        $parent->delete();

        // 3. Assert (Vérifier la suppression en cascade et la mise à jour des enfants)
        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        
        // Les Matériaux liés doivent être supprimés (via `cascade on delete` sur la clé étrangère ou Observer)
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        
        // La catégorie enfant doit exister, mais son parent_id doit être mis à null (doit être géré par un Observer ou la migration/base de données)
        $child->refresh();
        $this->assertDatabaseHas('categories', ['id' => $child->id]); // L'enfant n'est PAS supprimé
        $this->assertNull($child->parent_id);
    }
}

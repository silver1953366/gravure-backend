<?php

namespace Tests\Feature;

use App\Models\Dimension;
use App\Models\Inventory;
use App\Models\Material;
use App\Models\MaterialDimension;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MaterialDimensionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_material_dimension_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act (Créer la MaterialDimension via la Factory)
        $materialDimension = MaterialDimension::factory()->create();

        // 2. Assert (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('material_dimensions', [
            'id' => $materialDimension->id,
            'material_id' => $materialDimension->material_id,
            // Remarque: La Factory crée également les IDs des autres clés étrangères (shape_id, category_id)
        ]);
    }

    /** @test */
    public function it_belongs_to_a_material(): void
    {
        // 1. Arrange (Créer une MaterialDimension via la Factory)
        $materialDimension = MaterialDimension::factory()->create();

        // 2. Act & Assert
        // Vérifie la relation inverse: la MaterialDimension appartient à une Material
        $this->assertInstanceOf(Material::class, $materialDimension->material);
        $this->assertEquals($materialDimension->material_id, $materialDimension->material->id);
    }

    /** @test */
    public function it_belongs_to_a_dimension(): void
    {
        // 1. Arrange
        // Note: Ici, on suppose que le modèle MaterialDimension a une colonne dimension_id.
        // Si la colonne est shape_id comme suggéré par la Factory, ce test doit être adapté.
        $materialDimension = MaterialDimension::factory()->create();

        // 2. Act & Assert
        // Vérifie la relation inverse: la MaterialDimension appartient à une Dimension (à ajuster selon votre schéma)
        // Pour cet exemple, je garde 'dimension' mais cela pourrait être 'shape' selon le modèle MaterialDimension.
        $this->assertInstanceOf(Dimension::class, $materialDimension->dimension);
        $this->assertEquals($materialDimension->dimension_id, $materialDimension->dimension->id);
    }

    /** @test */
    public function it_has_an_inventory(): void
    {
        // 1. Arrange
        // Créer une MaterialDimension qui a un Inventaire lié (relation One-to-One)
        $materialDimension = MaterialDimension::factory()->has(Inventory::factory())->create();
        
        // 2. Act & Assert
        // Vérifie la relation: la MaterialDimension a un Inventaire
        $this->assertInstanceOf(Inventory::class, $materialDimension->inventory);
        $this->assertEquals($materialDimension->id, $materialDimension->inventory->material_dimension_id);
    }

    /** @test */
    public function the_material_id_and_dimension_id_combination_must_be_unique(): void
    {
        // 1. Arrange (Créer la première entrée avec une combinaison spécifique)
        $material = Material::factory()->create();
        $dimension = Dimension::factory()->create();

        MaterialDimension::factory()->create([
            'material_id' => $material->id,
            'dimension_id' => $dimension->id,
        ]);

        // 2. Act & Assert (Tenter de créer une seconde entrée avec la même combinaison)
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');
        
        // La tentative de création échoue car la clé composée (material_id, dimension_id) est unique.
        MaterialDimension::factory()->create([
            'material_id' => $material->id,
            'dimension_id' => $dimension->id,
        ]);
    }

    /** @test */
    public function deleting_material_cascades_and_deletes_material_dimension(): void
    {
        // 1. Arrange (Créer les entités liées)
        $material = Material::factory()->create();
        $materialDimension = MaterialDimension::factory()->create(['material_id' => $material->id]);
        
        $this->assertDatabaseHas('material_dimensions', ['id' => $materialDimension->id]);

        // 2. Act (Supprimer la Material)
        $material->delete();

        // 3. Assert (Vérifier que la MaterialDimension est supprimée)
        $this->assertDatabaseMissing('material_dimensions', ['id' => $materialDimension->id]);
    }

    /** @test */
    public function deleting_dimension_cascades_and_deletes_material_dimension(): void
    {
        // 1. Arrange (Créer les entités liées)
        $dimension = Dimension::factory()->create();
        $materialDimension = MaterialDimension::factory()->create(['dimension_id' => $dimension->id]);
        
        $this->assertDatabaseHas('material_dimensions', ['id' => $materialDimension->id]);

        // 2. Act (Supprimer la Dimension)
        $dimension->delete();

        // 3. Assert (Vérifier que la MaterialDimension est supprimée)
        $this->assertDatabaseMissing('material_dimensions', ['id' => $materialDimension->id]);
    }
}

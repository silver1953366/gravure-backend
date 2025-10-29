<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Inventory;
use App\Models\MaterialDimension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\QueryException;

class InventoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_inventory_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act (Créer un inventaire via la Factory)
        $inventory = Inventory::factory()->create();

        // 2. Assert (Vérifier qu'il existe dans la base de données)
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'material_dimension_id' => $inventory->material_dimension_id,
            'stock_quantity' => $inventory->stock_quantity,
        ]);
    }

    /** @test */
    public function stock_and_reserved_quantities_can_be_updated(): void
    {
        // Créer un inventaire initial
        $inventory = Inventory::factory()->create(['stock_quantity' => 100, 'reserved_quantity' => 10]);

        // Mettre à jour les quantités
        $inventory->stock_quantity = 50;
        $inventory->reserved_quantity = 5;
        $inventory->save();

        // Vérifier les nouvelles valeurs
        $this->assertEquals(50, $inventory->fresh()->stock_quantity);
        $this->assertEquals(5, $inventory->fresh()->reserved_quantity);
    }
    
    /** @test */
    public function an_inventory_entry_must_be_unique_per_material_dimension(): void
    {
        // 1. Arrange (Créer la première entrée)
        $materialDimension = MaterialDimension::factory()->create();
        Inventory::factory()->create(['material_dimension_id' => $materialDimension->id]);

        // 2. Act & Assert (Tenter de créer une seconde entrée avec le même ID)
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');

        // On crée la seconde entrée, ce qui devrait lever l'exception d'unicité (unique('material_dimension_id'))
        Inventory::factory()->create(['material_dimension_id' => $materialDimension->id]);
    }

    /** @test */
    public function scope_lowStock_returns_correct_inventories(): void
    {
        // Ceci simule un scope 'lowStock' ou une requête pour identifier les produits 
        // dont le stock est inférieur au seuil minimum.

        // 1. Arrange
        // Créer un inventaire en faible stock (Low Stock: stock < minimum_threshold)
        $lowStockInventory = Inventory::factory()->lowStock()->create();
        
        // Créer deux inventaires avec stock suffisant
        Inventory::factory()->create(['stock_quantity' => 200, 'minimum_threshold' => 10]);
        Inventory::factory()->create(['stock_quantity' => 50, 'minimum_threshold' => 40]);

        // 2. Act
        // Récupérer les inventaires où le stock est inférieur au seuil (simule le scope)
        $result = Inventory::whereColumn('stock_quantity', '<', 'minimum_threshold')->get();

        // 3. Assert
        $this->assertCount(1, $result);
        $this->assertEquals($lowStockInventory->id, $result->first()->id);
        $this->assertTrue($result->first()->stock_quantity < $result->first()->minimum_threshold);
    }

    /** @test */
    public function deleting_material_dimension_deletes_inventory_entry(): void
    {
        // 1. Arrange (Créer la dimension et l'inventaire lié)
        $materialDimension = MaterialDimension::factory()->create();
        $inventory = Inventory::factory()->create(['material_dimension_id' => $materialDimension->id]);

        $this->assertDatabaseHas('inventories', ['id' => $inventory->id]);

        // 2. Act (Supprimer la MaterialDimension - grâce au onDelete('cascade'))
        $materialDimension->delete();

        // 3. Assert (Vérifier que l'inventaire a été supprimé)
        $this->assertDatabaseMissing('inventories', ['id' => $inventory->id]);
    }
}

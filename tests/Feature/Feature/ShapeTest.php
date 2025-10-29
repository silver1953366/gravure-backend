<?php

namespace Tests\Feature;

use App\Models\Shape;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShapeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_shape_can_be_created_and_persisted(): void
    {
        // 1. Arrange & Act
        $shape = Shape::factory()->create(['name' => 'Octogonal']);
        
        // 2. Assert (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('shapes', [
            'id' => $shape->id,
            'name' => 'Octogonal',
            'slug' => 'octogonal',
            'is_active' => true, // Vérifie la valeur par défaut
        ]);
    }

    /** @test */
    public function the_shape_name_is_unique(): void
    {
        // 1. Arrange
        $name = 'Pentagone';
        Shape::factory()->create(['name' => $name]);

        // 2. Act & Assert: Tenter de créer un doublon doit échouer
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');
        Shape::factory()->create(['name' => $name]);
    }

    /** @test */
    public function a_shape_can_be_marked_as_inactive_using_factory_state(): void
    {
        // Utilise l'état 'inactive' de la factory
        $shape = Shape::factory()->inactive()->create();

        // Assertions
        $this->assertFalse($shape->is_active);
        $this->assertDatabaseHas('shapes', [
            'id' => $shape->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function its_slug_is_correctly_generated(): void
    {
        $name = 'Forme Spéciale N°7';
        $shape = Shape::factory()->create(['name' => $name]);

        $this->assertEquals('forme-speciale-n7', $shape->slug);
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * Le nom du modèle correspondant.
     */
    protected $model = Category::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(rand(2, 4), true);

        return [
            // Colonne 'name' (correspond à $table->string('name')->unique())
            'name' => $name,
            
            // NOTE IMPORTANTE: 'parent_id' a été retiré pour correspondre à votre migration, 
            // qui ne définit pas de colonne 'parent_id'.
            // 'parent_id' => null, // <-- Cette ligne causait l'erreur SQL.
            
            // Colonne 'description' (correspond à $table->text('description')->nullable())
            'description' => $this->faker->optional(0.7)->sentence(), // 70% de chances d'avoir une description
            
            // Colonne 'is_active' (correspond à $table->boolean('is_active')->default(true))
            'is_active' => $this->faker->boolean(85), // 85% de chances d'être actif
        ];
    }
    
    /**
     * State transformation: Marque la catégorie comme inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * State transformation: Lie la catégorie à un parent existant.
     * Cette méthode est inutile si 'parent_id' n'existe pas dans la base de données.
     * Elle a été supprimée pour éviter toute confusion future.
     */
    // public function childOf(Category $parent): Factory
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'parent_id' => $parent->id,
    //     ]);
    // }
}

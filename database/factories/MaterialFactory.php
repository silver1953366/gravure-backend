<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Material::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Liste de types de matériaux (Granit, Marbre, Métal, Plastique)
        $materialTypes = [
            'Granit Noir', 'Marbre Blanc', 'Acier Inoxydable Brossé', 'Aluminium Anodisé', 
            'Plexiglas Clair', 'Bois de Chêne', 'Bronze Poli', 'Pierre Bleue', 'Verre Trempé'
        ];
        
        $name = $this->faker->unique()->randomElement($materialTypes) . ' - ' . $this->faker->city;

        return [
            // Relations
            // 'user_id' => User::factory(), // ANCIEN CHAMP RETIRÉ: Il n'existe pas dans la migration materials
            'category_id' => Category::factory(),
            
            // Core Identity
            'name' => $name,
            'slug' => str()->slug($name),
            
            // Inventory & Pricing
            'sku' => $this->faker->unique()->ean13(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'stock_quantity' => $this->faker->numberBetween(0, 100),

            // Metadata
            'description' => $this->faker->optional(0.9)->realText(200),
            'image_url' => $this->faker->optional(0.8)->imageUrl(640, 480, 'material', true, 'Texture'), 
            'color' => $this->faker->optional(0.9)->hexColor(), 
            
            // Status
            'is_active' => $this->faker->boolean(80),
        ];
    }
    
    /**
     * State transformation: Marque le matériau comme inactif.
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

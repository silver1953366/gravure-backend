<?php

namespace Database\Factories;

use App\Models\Shape;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShapeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shape::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Liste de formes courantes
        $commonShapes = ['Plaque Rectangulaire', 'Rond', 'Cœur', 'Ovale', 'Étoile', 'Losange'];
        
        // Sélectionne un nom unique et garantit que le nom n'est pas déjà dans la base de données factice.
        $name = $this->faker->unique()->randomElement($commonShapes) . ' ' . $this->faker->randomNumber(2, true);

        return [
            // $table->string('name')->unique()
            'name' => $name,
            
            // $table->string('slug')->unique()->nullable()
            'slug' => str()->slug($name), 
            
            // $table->text('description')->nullable()
            'description' => $this->faker->optional(0.7)->paragraph(), // 70% de chance d'avoir une description plus longue
            
            // $table->string('image_url')->nullable()
            // Utilisation d'un placeholder d'image pour simuler un lien d'illustration.
            'image_url' => $this->faker->optional(0.5)->imageUrl(640, 480, 'abstract', true, 'Shape'), 
            
            // $table->boolean('is_active')->default(true)
            'is_active' => $this->faker->boolean(95), // Très forte chance d'être actif
        ];
    }
    
    /**
     * State transformation: Marque la forme comme inactive.
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialDimension;
use App\Models\Shape;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialDimensionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MaterialDimension::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Générer des dimensions réalistes pour le label (en cm)
        $width = $this->faker->numberBetween(10, 100);
        $height = $this->faker->numberBetween(10, 100);
        $dimensionLabel = "{$width}cm x {$height}cm";
        
        // Calculer un prix basé sur une surface approximative (pour le réalisme)
        $approxArea = $width * $height; // en cm²
        // Prix de base entre 0.5 FCFA et 5 FCFA par cm²
        $basePricePerCm2 = $this->faker->randomFloat(2, 0.5, 5); 
        $unitPriceFcfa = round($approxArea * $basePricePerCm2, -2); // Arrondi à la centaine pour un prix plus "commercial"

        return [
            // Clés étrangères
            // Assure qu'un Material, une Shape et une Category existent, ou les crée.
            'material_id' => Material::factory(),
            'shape_id' => Shape::factory(),
            'category_id' => Category::factory(), // Conservé pour la cohérence
            
            // $table->string('dimension_label', 255)
            'dimension_label' => $dimensionLabel,
            
            // $table->decimal('unit_price_fcfa', 10, 2)
            'unit_price_fcfa' => $unitPriceFcfa,

            // $table->boolean('is_active')->default(true)
            'is_active' => $this->faker->boolean(90),
        ];
    }
    
    /**
     * State transformation: Définit l'état pour une taille XL (plus cher).
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function extraLarge(): Factory
    {
        // Grandes dimensions (plus cher)
        $width = $this->faker->numberBetween(120, 200);
        $height = $this->faker->numberBetween(120, 200);
        $dimensionLabel = "{$width}cm x {$height}cm (XL)";
        $unitPriceFcfa = round($width * $height * $this->faker->randomFloat(2, 4, 8), -2);

        return $this->state(fn (array $attributes) => [
            'dimension_label' => $dimensionLabel,
            'unit_price_fcfa' => $unitPriceFcfa,
        ]);
    }
}

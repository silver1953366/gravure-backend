<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\MaterialDimension;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Assure qu'il existe toujours une MaterialDimension à laquelle se lier.
        $materialDimension = MaterialDimension::factory()->create();

        // Créer des quantités réalistes
        $stockQuantity = $this->faker->numberBetween(50, 500);
        $reservedQuantity = $this->faker->numberBetween(0, (int)($stockQuantity * 0.25)); // Réserve max 25% du stock

        return [
            'material_dimension_id' => $materialDimension->id,
            'stock_quantity'        => $stockQuantity,
            'reserved_quantity'     => $reservedQuantity,
            'minimum_threshold'     => $this->faker->numberBetween(10, 50),
            'price_per_unit'        => $this->faker->randomFloat(2, 5, 500), // Prix d'achat unitaire (coût)
            'last_restock_at'       => $this->faker->dateTimeThisYear(),
        ];
    }

    /**
     * Configure l'inventaire dans un état de rupture de stock (Low Stock).
     */
    public function lowStock(): Factory
    {
        return $this->state(function (array $attributes) {
            $stockQuantity = $this->faker->numberBetween(1, 9);
            return [
                'stock_quantity'    => $stockQuantity,
                // Assurez-vous que le seuil minimum est supérieur au stock
                'minimum_threshold' => $stockQuantity + $this->faker->numberBetween(5, 10), 
                'reserved_quantity' => $this->faker->numberBetween(0, $stockQuantity),
            ];
        });
    }

    /**
     * Configure l'inventaire dans un état de stock critique (Out of Stock).
     */
    public function outOfStock(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'stock_quantity'    => 0,
                'reserved_quantity' => 0,
                'minimum_threshold' => $this->faker->numberBetween(10, 20),
            ];
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Material;
use App\Models\MaterialDimension;
use App\Models\Quote;
use App\Models\Shape;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Quote::class;

    /**
     * Statique pour générer des références séquentielles
     * @var int
     */
    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // 1. Identification & Référence Séquentielle
        $year = date('Y');
        self::$sequence++;
        $reference = sprintf('QTE-%s-%04d', $year, self::$sequence);
        
        // 2. Logique et Pricing
        $quantity = $this->faker->numberBetween(1, 5);
        $isCustom = $this->faker->boolean(40); // 40% de chance d'être "Sur Mesure"
        $status = $this->faker->randomElement(['sent', 'calculated', 'ordered', 'rejected']);

        // Prix Unitaire de base (entre 50 000 et 500 000 FCFA)
        $unitPrice = $this->faker->randomFloat(2, 50000, 500000); 
        $basePrice = $unitPrice * $quantity;
        
        // Simulation de réduction (20% de chance d'avoir une réduction)
        $discountRate = $this->faker->boolean(20) ? $this->faker->randomElement([0.05, 0.10, 0.15]) : 0.00;
        $discountAmount = $basePrice * $discountRate;
        $finalPrice = $basePrice - $discountAmount;

        // 3. Détails
        $clientDetails = [
            'name' => $this->faker->name,
            'phone' => $this->faker->e164PhoneNumber(),
            'email' => $this->faker->unique()->safeEmail,
        ];

        $detailsSnapshot = [
            'text_content' => $this->faker->optional(0.6)->sentence(),
            'font' => $this->faker->optional(0.5)->randomElement(['Avenir', 'Roboto', 'Bebas Neue']),
            'color_code' => $this->faker->optional(0.7)->hexColor(),
            'instructions' => $this->faker->paragraph(2),
        ];

        return [
            // --- Clés et Identification ---
            'reference' => $reference,
            // Lie à un utilisateur existant (70% du temps) ou en crée un
            'user_id' => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray() ?? [null]) ?? User::factory(),
            'order_id' => null, 
            'client_details' => $clientDetails,
            
            // --- Configuration de la Pièce ---
            'material_id' => Material::factory(),
            'shape_id' => Shape::factory(),
            // Lie à MaterialDimension uniquement si ce n'est pas "Sur Mesure"
            'material_dimension_id' => $isCustom ? null : MaterialDimension::factory(), 
            // Lie à Discount uniquement si un rabais a été appliqué
            'discount_id' => $discountAmount > 0 ? Discount::factory() : null,
            
            // --- Logique du Devis ---
            'quantity' => $quantity,
            'dimension_label' => $isCustom ? 'Sur Mesure (Custom 120x80cm)' : 'Catalogue - 30cm x 50cm',
            'price_source' => $isCustom ? 'custom' : 'standard',
            'status' => $status,
            
            // --- Tarification (Snapshot historique) ---
            'unit_price_fcfa' => $unitPrice,
            'base_price_fcfa' => $basePrice,
            'discount_amount_fcfa' => $discountAmount,
            'final_price_fcfa' => $finalPrice,
            
            // --- Détails Techniques (Stockage JSON) ---
            'details_snapshot' => $detailsSnapshot,
        ];
    }
    
    /**
     * State transformation: Marque le devis comme brouillon (draft).
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function draft(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * State transformation: Marque le devis comme commandé (ordered).
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ordered(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ordered',
            // Simule un lien vers une commande (order_id)
            'order_id' => \App\Models\Order::factory(), 
        ]);
    }
}

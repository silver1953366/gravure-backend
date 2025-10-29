<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Détermination du type de rabais
        $type = $this->faker->randomElement(['percentage', 'fixed']);
        
        // Détermination de la valeur
        if ($type === 'percentage') {
            $value = $this->faker->randomElement([5, 10, 15, 20]);
            $name = "Rabais {$value}% sur le total";
        } else {
            // Montant fixe entre 5 000 et 50 000 FCFA
            $value = $this->faker->randomElement([5000, 10000, 25000, 50000]);
            $name = "Rabais fixe de " . number_format($value, 0, ',', ' ') . " FCFA";
        }

        // Création d'un code unique
        $codePrefix = Str::upper($type === 'percentage' ? 'PROMO' : 'FIXE');
        $code = $codePrefix . '_' . $this->faker->unique()->numberBetween(100, 999);
        
        // Date d'expiration (70% de chance d'expirer dans le futur)
        $expiresAt = $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+6 months');

        return [
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'value' => $value,
            // 30% de chance d'avoir un minimum de commande
            'min_order_amount' => $this->faker->boolean(30) ? $this->faker->randomElement([50000, 100000, 200000]) : 0, 
            'is_active' => $this->faker->boolean(80),
            'expires_at' => $expiresAt,
            'user_id' => null, // Ajout de la clé par défaut si non fourni.
        ];
    }
    
    /**
     * State transformation: Définit l'état pour un rabais qui est expiré.
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function expired(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            // La date d'expiration est dans le passé
            'expires_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * State transformation: Définit l'état pour un rabais de lancement (15%).
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function launchSpecial(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Offre de Lancement',
            'code' => 'LAUNCH15',
            'type' => 'percentage',
            'value' => 15.00,
            'min_order_amount' => 0,
            'is_active' => true,
            'expires_at' => Carbon::now()->addMonths(3),
        ]);
    }
}

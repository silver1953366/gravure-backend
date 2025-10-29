<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Le nom du modèle correspondant à la factory.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // On suppose que l'utilisateur et le devis doivent exister avant la commande.
        // On lie l'Order à un User et un Quote existants ou créés.
        $quote = Quote::factory()->create();
        
        return [
            // Relations
            'user_id' => $quote->user_id ?? User::factory(),
            'quote_id' => $quote->id,
            
            // Numéro de référence unique (simule la logique du contrôleur)
            'order_number' => 'CMD-' . date('Ymd') . '-' . Str::upper(Str::random(6)) . '-' . $this->faker->unique()->randomNumber(4),
            
            // Prix total (basé sur le prix du devis)
            'total_amount' => $quote->final_price_fcfa,
            
            // Détails du produit (copie des champs du devis pour la 'snapshot')
            'material_id' => $quote->material_id,
            'shape_id' => $quote->shape_id,
            'quantity' => $quote->quantity,
            
            // Statut par défaut (doit correspondre à une constante définie dans le modèle Order)
            'status' => 'pending_payment', // Correspond à Order::STATUS_PENDING_PAYMENT
            
            // Adresse de livraison (stockée en JSON dans la BDD)
            'shipping_address' => [
                'name' => $this->faker->name,
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'postal_code' => $this->faker->postcode,
                'country' => $this->faker->countryCode,
            ],

            // Champs d'horodatage
            'completed_at' => null, // Par défaut, non complété
            'payment_id' => null,
        ];
    }

    /**
     * Indique que la commande est terminée et définit l'horodatage de complétion.
     * Utilisé pour les tests nécessitant le champ `completed_at`.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indique que la commande est payée (mais pas nécessairement terminée).
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function paid(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'payment_id' => 'PAY-' . Str::upper(Str::random(10)),
        ]);
    }
}

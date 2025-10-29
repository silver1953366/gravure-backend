<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Le nom du modèle correspondant.
     *
     * @var string
     */
    protected $model = Notification::class;

    /**
     * Définir l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // L'utilisateur destinataire de la notification
            'user_id' => User::factory(), 
            
            'type' => $this->faker->randomElement(['order_placed', 'quote_accepted', 'new_material', 'system_alert']),
            
            'message' => $this->faker->sentence(),
            
            // Par défaut, la notification est non lue, l'état 'read' gérera le marquage.
            'read_at' => null, 
            
            // La colonne data est stockée en JSON
            'data' => json_encode(['related_id' => $this->faker->numberBetween(1, 100)]),
        ];
    }
    
    /**
     * State transformation: Marque la notification comme lue.
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function read(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
    
    /**
     * State transformation: Marque la notification comme non lue (État par défaut, mais utile pour la clarté).
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unread(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use App\Models\Order; // Modèle traçable 1
use App\Models\Quote; // Modèle traçable 2
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Le nom du modèle correspondant à la factory.
     */
    protected $model = Activity::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // On choisit aléatoirement un modèle cible pour simuler l'action
        $targetModels = [Order::class, Quote::class];
        
        $modelClass = $this->faker->randomElement($targetModels);
        $modelInstance = $modelClass::factory()->create();

        // Définition de l'action
        $action = $this->faker->randomElement([
            'quote_created', 
            'order_status_changed', 
            'user_profile_updated', 
            'system_cleanup',
        ]);
        
        // Simuler un instantané de données pour l'action 'order_status_changed'
        $dataSnapshot = ($action === 'order_status_changed') ? [
            'before' => ['status' => 'paid'],
            'after' => ['status' => 'processing'],
        ] : null;

        return [
            // Lier à un utilisateur (90% de chance d'être lié, 10% null pour les actions système)
            'user_id' => $this->faker->boolean(90) ? User::factory() : null, 
            
            'action' => $action,
            
            // Configuration de la relation polymorphique (MorphTo)
            'model_type' => $modelInstance->getMorphClass(),
            'model_id' => $modelInstance->id,
            
            'data_snapshot' => $dataSnapshot,
            
            'ip_address' => $this->faker->ipv4,
        ];
    }
    
    /**
     * État pour les actions de connexion/déconnexion qui n'affectent pas un modèle spécifique.
     */
    public function authentication(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'action' => $this->faker->randomElement(['user_logged_in', 'user_logged_out']),
            'model_type' => null,
            'model_id' => null,
            'data_snapshot' => null,
        ]);
    }
}

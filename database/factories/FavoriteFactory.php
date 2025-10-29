<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Material; // Modèle de test commun pour la relation polymorphique
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Favorite>
 */
class FavoriteFactory extends Factory
{
    /**
     * Le nom du modèle correspondant.
     *
     * @var string
     */
    protected $model = Favorite::class;

    /**
     * Définir l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // On crée un modèle Material par défaut pour être l'élément favorisé
        $favoritable = Material::factory()->create();

        return [
            'user_id' => User::factory(), // L'utilisateur qui a mis l'élément en favori

            // La relation poly-morphique: l'élément favorisé
            'favoritable_id' => $favoritable->id,
            'favoritable_type' => $favoritable::class,
        ];
    }
}

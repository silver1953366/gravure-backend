<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Material; // Exemple de modèle traçable
use App\Models\Quote;    // Ajout d'un modèle de notre projet (Devis)
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Le nom du modèle correspondant.
     */
    protected $model = Attachment::class;

    /**
     * Définit l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // On choisit aléatoirement un modèle cible traçable (Devis, Matériau, etc.)
        $attachableModels = [Material::class, Quote::class];
        
        $modelClass = $this->faker->randomElement($attachableModels);
        $attachable = $modelClass::factory()->create();

        $fileName = $this->faker->slug() . '_' . Str::random(5) . '.' . $this->faker->randomElement(['jpg', 'png', 'pdf', 'xlsx']);
        
        // Détermination du MIME type basé sur l'extension pour plus de réalisme
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };

        return [
            // L'utilisateur qui a téléchargé la pièce jointe
            'user_id' => User::factory(), 
            
            // Relation polymorphique
            'attachable_id' => $attachable->id,
            'attachable_type' => $attachable->getMorphClass(),
            
            'name' => $fileName,
            // Simule un chemin de stockage
            'path' => 'uploads/' . Str::random(20) . '/' . $fileName, 
            'disk' => 's3', // Utilisation d'un disque cloud par défaut (plus commun en prod)
            'mime_type' => $mimeType,
            'size' => $this->faker->numberBetween(50 * 1024, 10 * 1024 * 1024), // 50KB à 10MB
        ];
    }
}

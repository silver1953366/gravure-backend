<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Carousel extends Model
{
    use HasFactory;

    /**
     * Nom de la table associée.
     */
    protected $table = 'carousels';

    /**
     * Les attributs qui peuvent être assignés massivement.
     * On ajoute 'height' pour le contrôle des dimensions côté interface.
     */
    protected $fillable = [
        'title',
        'subtitle',
        'image_url',     // Chemin relatif (ex: carousel/image.jpg)
        'link',          // URL de redirection
        'category_name', // Texte personnalisé du bouton
        'order',         // Ordre de priorité (unique géré par le controller)
        'height',        // Hauteur personnalisée en pixels
        'is_active',     // Statut de visibilité
    ];

    /**
     * Cast des types pour assurer la cohérence JSON avec le Frontend (Angular).
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
        'height'    => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Ajoute automatiquement 'full_image_url' dans l'objet JSON envoyé à l'API.
     */
    protected $appends = ['full_image_url'];

    /**
     * ACCESSEUR : full_image_url
     * Génère l'URL absolue pour l'affichage de l'image.
     */
    public function getFullImageUrlAttribute(): string
    {
        // 1. Si aucune image n'est définie en base
        if (!$this->image_url) {
            return asset('images/defaults/placeholder-carousel.jpg');
        }

        // 2. Si l'image stockée est déjà une URL complète (ex: via un Seeder ou un CDN externe)
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }

        // 3. Retourne l'URL via le storage public
        // Nécessite d'avoir fait : php artisan storage:link
        return asset(Storage::url($this->image_url));
    }

    /**
     * LOGIQUE DE CYCLE DE VIE (Booted)
     * Actions automatiques lors des événements Eloquent.
     */
    protected static function booted()
    {
        /**
         * Événement de suppression : Nettoyage physique du disque.
         * Évite de garder des images orphelines dans storage/app/public/carousel.
         */
        static::deleting(function ($carousel) {
            if ($carousel->image_url) {
                // On ne supprime que si le fichier existe et n'est pas une URL externe
                if (!filter_var($carousel->image_url, FILTER_VALIDATE_URL)) {
                    if (Storage::disk('public')->exists($carousel->image_url)) {
                        Storage::disk('public')->delete($carousel->image_url);
                    }
                }
            }
        });
    }
}
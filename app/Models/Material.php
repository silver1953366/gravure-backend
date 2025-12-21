<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    /**
     * Les attributs qui peuvent être assignés en masse.
     * IMPORTANT : J'ai ajouté price_per_sq_meter, thickness_options et is_active.
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image_url',
        'price_per_sq_meter', // Ajouté pour le calcul des prix
        'thickness_options',  // Ajouté pour les options du configurateur
        'is_active',          // Ajouté pour la gestion du statut
        'color',
    ];

    /**
     * Conversion automatique des types.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'price_per_sq_meter' => 'decimal:2',
    ];

    // --- Relations ---

    /**
     * Récupère la catégorie à laquelle appartient le matériau.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Récupère les dimensions/prix standards associés à ce matériau.
     */
    public function materialDimensions(): HasMany
    {
        return $this->hasMany(MaterialDimension::class);
    }
    
    /**
     * Récupère les devis qui utilisent ce matériau.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
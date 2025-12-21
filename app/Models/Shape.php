<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shape extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_url',
        'slug',          // Ajouté
        'description',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- Relations ---

    /**
     * Récupère les entrées de prix de catalogue liées à cette forme.
     */
    public function materialDimensions(): HasMany
    {
        // Renommé de 'dimensions' à 'materialDimensions' pour la clarté
        return $this->hasMany(MaterialDimension::class);
    }

    /**
     * Récupère les devis qui utilisent cette forme.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
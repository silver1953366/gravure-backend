<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug', // 💡 AJOUT CLÉ pour le filtrage frontend
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean', 
    ];

    // --- Relations ---

    /**
     * Récupère tous les matériaux qui appartiennent à cette catégorie.
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }
    
    /**
     * Récupère toutes les entrées de prix de catalogue liées à cette catégorie.
     */
    public function materialDimensions(): HasMany
    {
        return $this->hasMany(MaterialDimension::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne; // Ajouté pour la relation 'inventory'

class MaterialDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'shape_id',
        'category_id',
        
        'dimension_label',
        'unit_price_fcfa',
        
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- Relations ---

    /**
     * Récupère le matériau associé.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Récupère la forme associée.
     */
    public function shape(): BelongsTo
    {
        return $this->belongsTo(Shape::class);
    }

    /**
     * Récupère la catégorie associée.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    /**
     * Récupère tous les devis qui utilisent cette entrée du catalogue.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'material_dimension_id');
    }

    /**
     * Récupère l'entrée d'inventaire associée (s'il s'agit d'une relation 1:1)
     * Si plusieurs entrées d'inventaire existent (ex: différents lots/locations), changez HasOne en HasMany.
     */
    public function inventory(): HasOne
    {
        // On suppose que la clé étrangère est 'material_dimension_id' dans la table 'inventories'
        return $this->hasOne(Inventory::class, 'material_dimension_id');
    }
}

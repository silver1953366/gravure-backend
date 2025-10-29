<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_dimension_id',
        'stock_quantity',
        'reserved_quantity',
        'minimum_threshold',
        'last_restock_at',
        'price_per_unit', // <-- AJOUTÉ : Assurez-vous que le nom correspond à votre colonne
    ];

    /**
     * L'inventaire appartient à une entrée de prix/dimension spécifique.
     */
    public function materialDimension(): BelongsTo
    {
        return $this->belongsTo(MaterialDimension::class);
    }
    
    /**
     * Calcule la quantité disponible réelle.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }
}

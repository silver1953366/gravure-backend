<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'material_dimension_id',
        'quantity',
        'fixed_unit_price_fcfa',
        'engraving_text',
        'mounting_option',
        'custom_options',
    ];

    protected $casts = [
        'fixed_unit_price_fcfa' => 'decimal:2',
        'custom_options' => 'json', // Utiliser le casting JSON
    ];

    // --- RELATIONS ---

    /**
     * L'article appartient à un panier.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * L'article est lié à une ligne de catalogue de prix.
     */
    public function materialDimension(): BelongsTo
    {
        return $this->belongsTo(MaterialDimension::class);
    }

    // --- ACCESSEURS (si besoin de calculer le prix total HT) ---

    public function getTotalPriceFcfaAttribute(): float
    {
        return $this->quantity * $this->fixed_unit_price_fcfa;
    }
}
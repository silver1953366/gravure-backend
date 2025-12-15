<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'discount_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // --- RELATIONS ---

    /**
     * Le panier appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le panier a plusieurs articles.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
    
    /**
     * Le panier a un rabais appliqué.
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
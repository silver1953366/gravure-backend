<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Discount
 * Représente un rabais applicable aux devis/commandes.
 */
class Discount extends Model
{
    use HasFactory;
    
    // BONNE PRATIQUE: Définition des constantes pour les types de rabais
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'name',
        'code', // AJOUT: Le code que l'utilisateur saisit (ex: ETE20)
        'type',
        'value',
        'min_order_amount',
        'is_active',
        'expires_at', // OPTIONNEL: Si les réductions ont une date d'expiration
    ];

    protected $casts = [
        'value' => 'float',
        'min_order_amount' => 'float',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // --- Relations ---

    /**
     * Récupère tous les devis qui ont utilisé ce rabais.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
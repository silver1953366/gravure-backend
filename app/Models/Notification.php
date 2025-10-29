<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    // BONNE PRATIQUE: Constantes pour les types de notifications
    const TYPE_QUOTE_UPDATED = 'quote_update';
    const TYPE_ORDER_SHIPPED = 'order_shipped';
    const TYPE_ACCOUNT_ALERT = 'account_alert';

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'is_read',
        'resource_id',   // AJOUT: ID de la ressource concernée (Quote, Order)
        'resource_type', // AJOUT: Type de la ressource (App\Models\Quote, App\Models\Order)
    ];

    protected $casts = [
        'is_read' => 'boolean', // Cast pour la facilité d'utilisation
    ];
    
    // Ajout d'une relation polymorphique dynamique pour lier à Quote ou Order
    public function resource()
    {
        return $this->morphTo();
    }

    /**
     * Récupère l'utilisateur qui a reçu cette notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
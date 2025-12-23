<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    // --- Constantes de Types (Pour le style visuel dans Angular) ---
    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';

    public const TYPES = [
        self::TYPE_INFO,
        self::TYPE_SUCCESS,
        self::TYPE_WARNING,
        self::TYPE_ERROR,
    ];

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
        'resource_id',   // ID du Devis ou de la Commande
        'resource_type', // Nom du modèle (App\Models\Quote ou App\Models\Order)
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- HELPER STATIQUE ---

    /**
     * Méthode simplifiée pour envoyer une notification rapidement.
     * Utilisée dans les Observers.
     */
    public static function send($userId, $type, $title, $message, $resource = null, $link = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'resource_id' => $resource ? $resource->id : null,
            'resource_type' => $resource ? get_class($resource) : null,
            'is_read' => false,
        ]);
    }

    // --- Relations ---

    /**
     * L'utilisateur destinataire de la notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation Polymorphique : Permet de lier la notification à n'importe quel objet
     * (un Devis, une Commande, etc.) sans créer de colonnes spécifiques.
     */
    public function resource(): MorphTo
    {
        return $this->morphTo();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo; // Ajout de l'import

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'data_snapshot',
        'ip_address',
    ];

    protected $casts = [
        'data_snapshot' => 'array',
    ];

    /**
     * Relation vers l'utilisateur qui a effectué l'activité.
     */
    public function user(): BelongsTo
    {
        // On suppose que le modèle User existe
        return $this->belongsTo(User::class);
    }

    /**
     * Définit la relation polymorphique vers le modèle qui a été affecté (Quote, Order, etc.).
     */
    public function model(): MorphTo
    {
        // Récupère l'objet lié en utilisant model_type et model_id
        return $this->morphTo();
    }
}

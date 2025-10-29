<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quote_id',
    ];

    /**
     * Récupère l'utilisateur associé à ce favori.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Récupère le devis (configuration de plaque) associé à ce favori.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
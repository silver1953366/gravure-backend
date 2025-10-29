<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'user_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
    ];

    /**
     * Un fichier joint appartient à un devis.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
    
    /**
     * Un fichier joint appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
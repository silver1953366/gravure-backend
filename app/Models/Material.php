<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image_url',
        'color',
    ];

    // --- Relations ---

    /**
     * Récupère la catégorie à laquelle appartient le matériau.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Récupère les dimensions/prix standards associés à ce matériau.
     */
    public function materialDimensions(): HasMany
    {
        return $this->hasMany(MaterialDimension::class);
    }
    
    /**
     * Récupère les devis qui utilisent ce matériau.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
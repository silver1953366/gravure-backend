<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon; 

class Order extends Model
{
    use HasFactory;

    // --- Constantes de Statut (Doivent correspondre à la migration) ---
    public const STATUS_PENDING_PAYMENT = 'pending_payment'; // En attente de paiement
    public const STATUS_PAID = 'paid';                      // Payée
    public const STATUS_PROCESSING = 'processing';          // En cours de traitement (production)
    public const STATUS_SHIPPED = 'shipped';                // Expédiée
    public const STATUS_COMPLETED = 'completed';            // Terminée (livrée)
    public const STATUS_CANCELLED = 'canceled';             // Annulée

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'user_id',
        'quote_id',
        'reference',
        'payment_id', 
        'final_price_fcfa',
        'shipping_address',
        'status',
        'completed_at', 
        // Champs de "Snapshot" copiés du devis
        'material_id',
        'shape_id',
        'material_dimension_id',
        'quantity',
        'client_details',
        'details_snapshot',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'shipping_address' => 'array',
        'client_details' => 'array',
        'details_snapshot' => 'array',
        'final_price_fcfa' => 'float',
        'quantity' => 'integer',
        'completed_at' => 'datetime', // Cast pour gérer Carbon\DateTime
    ];

    // --- MUTATOR ---
    /**
     * Définit le statut et gère la date 'completed_at'
     * si le statut passe à 'completed'.
     */
    public function setStatusAttribute($value)
    {
        // 1. Met à jour le statut
        $this->attributes['status'] = $value;

        // 2. Logique pour completed_at
        if ($value === self::STATUS_COMPLETED && $this->attributes['completed_at'] === null) {
            // Si la commande est terminée et n'avait pas de date de fin, on l'ajoute.
            $this->attributes['completed_at'] = Carbon::now();
        } 
        elseif ($value !== self::STATUS_COMPLETED && $this->isDirty('status')) {
            // Si le statut change vers autre chose que COMPLETED (et que le champ status a changé),
            // on réinitialise completed_at. Ceci permet de rouvrir une commande par erreur.
            $this->attributes['completed_at'] = null;
        }
    }


    // --- Relations ---

    /**
     * L'utilisateur qui a passé la commande.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le devis qui a généré cette commande.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Le type de matériel utilisé (Snapshot).
     */
    public function material(): BelongsTo
    {
        // Assurez-vous que Material existe
        // return $this->belongsTo(Material::class);
        return $this->belongsTo(\App\Models\Material::class);
    }

    /**
     * La forme du produit (Snapshot).
     */
    public function shape(): BelongsTo
    {
        // Assurez-vous que Shape existe
        // return $this->belongsTo(Shape::class);
        return $this->belongsTo(\App\Models\Shape::class);
    }

    /**
     * La dimension du matériel utilisée (Snapshot).
     */
    public function materialDimension(): BelongsTo
    {
        // Assurez-vous que MaterialDimension existe
        // return $this->belongsTo(MaterialDimension::class);
        return $this->belongsTo(\App\Models\MaterialDimension::class);
    }

    /**
     * Les pièces jointes liées à la commande.
     */
    public function attachments(): HasMany
    {
        // Assurez-vous que Attachment existe
        // return $this->hasMany(Attachment::class);
        return $this->hasMany(\App\Models\Attachment::class);
    }
}

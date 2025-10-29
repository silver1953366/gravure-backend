<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Quote
 *
 * @property int $id
 * @property string $reference
 * @property int $user_id
 * @property array $client_details Détails du client au moment de la demande (Nom, Email, Téléphone)
 * @property int $material_id
 * @property int $shape_id
 * @property int $material_dimension_id
 * @property int $quantity Quantité demandée
 * @property string $price_source Type de source de prix (e.g., 'auto', 'manual')
 * @property float $unit_price_fcfa Prix unitaire avant quantité et discount
 * @property string $dimension_label Dimension sélectionnée (e.g., '10x15cm', pour l'historique)
 * @property int|null $discount_id ID de la réduction appliquée
 * @property float $base_price_fcfa Prix total (Unit x Quantity) avant réduction
 * @property float $discount_amount_fcfa Montant de la réduction appliquée
 * @property float $final_price_fcfa Prix final après réduction (Net à payer)
 * @property string $status Statut du devis (draft, calculated, ordered, etc.)
 * @property array $details_snapshot Données de configuration du produit (Gravure, police, etc.)
 * @property int|null $order_id ID de la commande associée si le devis a été converti
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Material $material
 * @property-read \App\Models\Shape $shape
 * @property-read \App\Models\MaterialDimension $materialDimension
 * @property-read \App\Models\Discount|null $discount
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Order|null $order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Favorite> $favorites
 * @method static \Illuminate\Database\Eloquent\Builder|Quote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Quote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Quote query()
 */
class Quote extends Model
{
    use HasFactory;

    // Constantes de statut pour le contrôle de l'état du devis
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    // AJOUT ESSENTIEL : Constantes de rôle nécessaires pour débloquer les vérifications dans le contrôleur
    public const ROLE_ADMIN = 'admin';
    public const ROLE_CONTROLLER = 'controller';
    public const ROLE_CLIENT = 'client';


    /**
     * Les attributs qui peuvent être assignés en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'user_id',
        'client_details',
        'material_id',
        'shape_id',
        'material_dimension_id',
        'quantity',
        'price_source',
        'unit_price_fcfa',
        'dimension_label',
        'discount_id',
        'base_price_fcfa',
        'discount_amount_fcfa',
        'final_price_fcfa',
        'status',
        'details_snapshot',
        'order_id', // Rendu fillable au cas où vous vouliez le définir manuellement
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price_fcfa' => 'float',
        'base_price_fcfa' => 'float',
        'discount_amount_fcfa' => 'float',
        'final_price_fcfa' => 'float',
        'details_snapshot' => 'array',
        'client_details' => 'array',
    ];

    // --- Relations d'Appartenance (BelongsTo) ---

    /**
     * Le devis appartient à un utilisateur (client ou admin).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le devis est lié à un Matériau.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Le devis est lié à une Forme.
     */
    public function shape(): BelongsTo
    {
        return $this->belongsTo(Shape::class);
    }

    /**
     * Le devis est lié à une Dimension spécifique du Matériau.
     */
    public function materialDimension(): BelongsTo
    {
        // Spécification explicite de la clé étrangère pour clarté
        return $this->belongsTo(MaterialDimension::class, 'material_dimension_id');
    }

    /**
     * Le devis est lié à un Discount (optionnel).
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    // --- Relations Inverse (HasOne/HasMany) ---

    /**
     * Un devis peut avoir une seule commande associée.
     * Cette relation utilise la colonne 'order_id' sur la table 'quotes' (c'est une relation peu commune mais valide).
     */
    public function order(): HasOne
    {
        // Utilise order_id sur la table quotes pour pointer vers l'Order
        return $this->hasOne(Order::class, 'id', 'order_id');
    }

    /**
     * Un devis peut être dans les favoris de l'utilisateur.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Un devis peut avoir plusieurs pièces jointes (images/fichiers de conception).
     */
    public function attachments(): HasMany
    {
        // Un Attachment "appartient" à un Quote via la colonne quote_id
        return $this->hasMany(Attachment::class, 'quote_id');
    }
}

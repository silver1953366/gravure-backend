<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // BONNE PRATIQUE: Définition des constantes de rôle pour éviter les erreurs de frappe (Suggestion)
    const ROLE_ADMIN = 'admin';
    const ROLE_CONTROLLER = 'controller';
    const ROLE_CLIENT = 'client';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', 
        'phone',    // AJOUTÉ
        'address',  // AJOUTÉ
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- Relations ---

    /**
     * L'utilisateur a plusieurs devis.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * L'utilisateur a plusieurs commandes.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * L'utilisateur a plusieurs favoris (devis).
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * L'utilisateur a plusieurs notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
    
    // --- Helpers pour le Middleware/Contrôleur ---
    
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
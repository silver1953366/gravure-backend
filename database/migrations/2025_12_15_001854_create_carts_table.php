<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations (crée la table 'carts').
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Lien vers l'utilisateur (optionnel si le panier est anonyme)
            // Assurez-vous que la table 'users' existe
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('Utilisateur propriétaire du panier');
            
            // Jeton pour les paniers anonymes (à gérer en session côté Laravel)
            $table->string('session_token')->nullable()->unique()->comment('Jeton de session pour retrouver un panier anonyme.');
            
            // Lien vers un rabais appliqué (référence à la table discounts)
            // Assurez-vous que la table 'discounts' existe
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null')->comment('Code de rabais appliqué au total du panier.');

            // Statut du panier (pour le différencier d'un devis déjà créé)
            $table->enum('status', ['pending', 'quoted', 'ordered'])->default('pending')->comment('Statut du panier (en cours, converti en devis, commandé).');

            $table->timestamps();
        });
    }

    /**
     * Annule les migrations (supprime la table 'carts').
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
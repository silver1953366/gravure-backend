<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations (crée la table 'cart_items').
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            // Lien vers le panier parent
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            
            // Lien vers l'entrée du catalogue qui définit le prix de base
            $table->foreignId('material_dimension_id')->constrained('material_dimensions')->onDelete('cascade')->comment('Lien vers la dimension de matériel du catalogue.');

            // --- Détails de la commande et du prix ---
            $table->integer('quantity')->default(1);
            
            // FIXATION DU PRIX: Prix unitaire total, enregistré lors de l'ajout
            $table->decimal('fixed_unit_price_fcfa', 10, 2)->comment('Prix unitaire total (base + options) fixé au moment de l\'ajout.');
            
            // --- Options de Personnalisation (CORRIGÉ : STRING pour permettre l'indexation) ---
            // Si le texte dépasse 255 caractères, cette contrainte d'unicité devra être retirée ou le type devra rester TEXT avec une solution plus complexe.
            $table->string('engraving_text', 255)->nullable()->comment('Texte de gravure ou inscription souhaitée.');
            $table->string('mounting_option')->nullable()->comment('Option de fixation (ex: vis, adhésif, rien).');
            
            // Détails additionnels JSON (pour flexibilité future : police, couleur, position, etc.)
            $table->json('custom_options')->nullable()->comment('Options de personnalisation supplémentaires (JSON).');

            $table->timestamps();
            
            // CORRECTION: Contrainte d'unicité simplifiée grâce à l'utilisation de STRING
            $table->unique(
                ['cart_id', 'material_dimension_id', 'engraving_text', 'mounting_option'],
                'cart_item_unique_config' // Nom de l'index court
            ); 
        });
    }

    /**
     * Annule les migrations (supprime la table 'cart_items').
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
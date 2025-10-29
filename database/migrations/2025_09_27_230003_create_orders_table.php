<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // --- Keys and References ---
            $table->string('reference')->unique()->comment('Référence unique de la commande (e.g., CMD-A-0001)');
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('restrict')->comment('Devis converti en commande');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->comment('Utilisateur qui a passé la commande');
            $table->unique('quote_id'); // Une seule commande par devis
            
            // --- Transaction and Product Details (Snapshots) ---
            $table->string('payment_id')->nullable()->comment('ID de la transaction de la plateforme de paiement (Stripe, etc.)');
            $table->decimal('final_price_fcfa', 12, 2)->comment('Prix final payé (en FCFA)'); 
            
            // Product Snapshots (for historical consistency)
            // On peut rendre ces champs nullables si la relation n'est pas strictement obligatoire au niveau de la DB
            $table->foreignId('material_id')->constrained('materials')->onDelete('restrict')->comment('Snapshot du matériau'); 
            $table->foreignId('shape_id')->constrained('shapes')->onDelete('restrict')->comment('Snapshot de la forme'); 
            $table->foreignId('material_dimension_id')->nullable()->constrained('material_dimensions')->onDelete('restrict')->comment('Snapshot de la dimension du matériau (if applicable)'); 
            $table->unsignedInteger('quantity')->default(1)->comment('Quantité commandée'); 
            $table->json('client_details')->nullable()->comment('Snapshot des détails client (work name, etc.)'); 
            $table->json('details_snapshot')->nullable()->comment('Snapshot des détails de configuration du produit'); 

            // --- Logistics and Status ---
            $table->json('shipping_address')->comment('Adresse de livraison au moment de la commande (snapshot)'); 
            
            // ENUM values must match model constants (lowercase)
            $table->enum('status', [
                'pending_payment', 
                'paid', 
                'processing', 
                'shipped', 
                'completed', 
                'canceled' 
            ])->default('pending_payment'); 

            // --- Ajout du champ pour la date de complétion ---
            $table->timestamp('completed_at')->nullable()->comment('Date à laquelle la commande a été livrée/terminée');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

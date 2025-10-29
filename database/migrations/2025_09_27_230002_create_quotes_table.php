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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            
            // --- Clés et Identification ---
            $table->string('reference', 20)->unique()->comment('Référence unique du devis (ex: QTE-2024-0001)');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Lien vers l\'utilisateur enregistré');
            $table->unsignedBigInteger('order_id')->nullable()->comment('Lien vers la commande (contrainte FK ajoutée plus tard)');
            $table->json('client_details')->comment('Nom, téléphone, email du client, pour l\'historique');
            
            // --- Configuration de la Pièce (Snapshot des ID) ---
            // Ces champs sont conservés pour faciliter les relations d'affichage, même si les détails sont dans details_snapshot
            $table->foreignId('material_id')->constrained('materials')->onDelete('restrict')->comment('Matériau utilisé');
            $table->foreignId('shape_id')->constrained('shapes')->onDelete('restrict')->comment('Forme de la pièce');
            $table->foreignId('material_dimension_id')->nullable()->constrained('material_dimensions')->onDelete('set null')->comment('Ligne de prix fixe utilisée (si applicable)');
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('set null')->comment('Code de réduction utilisé');
            
            // --- Logique du Devis ---
            $table->unsignedInteger('quantity')->default(1);
            $table->string('dimension_label')->comment('Description des dimensions (ex: 30x50cm ou Sur Mesure)');
            $table->string('price_source', 50)->comment('Source du prix: standard, special, custom');
            $table->enum('status', ['draft', 'sent', 'calculated', 'ordered', 'rejected', 'archived'])->default('sent');
            
            // --- Tarification (Snapshot historique, crucial pour l'audit) ---
            $table->decimal('unit_price_fcfa', 10, 2);
            $table->decimal('base_price_fcfa', 12, 2)->comment('Prix total avant rabais (unit_price * quantity)');
            $table->decimal('discount_amount_fcfa', 12, 2)->default(0.00);
            $table->decimal('final_price_fcfa', 12, 2)->comment('Prix final après rabais');
            
            // --- Détails Techniques (Stockage JSON de toutes les spécifications) ---
            $table->json('details_snapshot')->comment('Snapshot complet des specs: texte, police, couleur, fichiers, instructions, etc.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
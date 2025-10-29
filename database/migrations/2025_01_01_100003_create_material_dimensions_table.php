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
        Schema::create('material_dimensions', function (Blueprint $table) {
            $table->id();

            // Les clés étrangères
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade')->comment('Lien vers le matériau');
            $table->foreignId('shape_id')->constrained('shapes')->onDelete('cascade')->comment('Lien vers la forme');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade')->comment('Lien vers la catégorie (conservé pour la cohérence du modèle)');
            
            // CORRECTION 1: Remplacement des champs width/height par un label
            $table->string('dimension_label', 255)->comment('Description textuelle de la dimension (ex: 30cm x 50cm)');
            
            // CORRECTION 2: Alignement du nom de colonne du prix sur le modèle
            $table->decimal('unit_price_fcfa', 10, 2)->comment('Prix unitaire fixe en FCFA pour cette ligne de catalogue');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // CORRECTION 3: Contrainte d'unicité basée sur les clés étrangères et le label
            $table->unique(['material_id', 'shape_id', 'dimension_label'], 'unique_material_dim');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_dimensions');
    }
};
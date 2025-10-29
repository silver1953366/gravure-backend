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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            // Liaison au prix fixe/dimension spécifique du catalogue
            $table->foreignId('material_dimension_id')->constrained('material_dimensions')->onDelete('cascade');
            
            // Quantité disponible.
            $table->unsignedInteger('stock_quantity')->default(0); 
            $table->unsignedInteger('reserved_quantity')->default(0); 
            $table->unsignedInteger('minimum_threshold')->default(10); 
            
            // AJOUT CRITIQUE : La colonne qui manquait
            $table->decimal('price_per_unit', 10, 2)->default(0.00); 
            
            $table->timestamp('last_restock_at')->nullable();
            
            $table->timestamps();
            
            // Assure qu'une seule entrée d'inventaire existe par MaterialDimension
            $table->unique('material_dimension_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
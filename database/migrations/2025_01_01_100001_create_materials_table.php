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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null')->comment('Catégorie de matériel (marbre, bois, métal, etc.)');
            
            $table->string('name')->unique()->comment('Nom du matériau (ex: Granit Noir, Acier Inoxydable)');
            $table->string('slug')->unique()->nullable()->comment('Slug pour URL ou référence.'); 
            $table->string('sku')->unique()->nullable()->comment('Code SKU (Stock Keeping Unit) du matériau.');
            
            // --- CORRECTIONS pour correspondre aux Factories de test ---
            $table->decimal('price', 10, 2)->nullable()->comment('Prix unitaire pour les tests. À supprimer si la tarification est dans MaterialDimension.');
            $table->integer('stock_quantity')->default(0)->comment('Quantité en stock disponible.');
            // ---------------------------------------------------------
            
            $table->text('description')->nullable();
            
            $table->string('image_url')->nullable();
            $table->string('color')->nullable()->comment('Code couleur ou nom de la couleur du matériau.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};

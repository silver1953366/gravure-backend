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
            
            // --- RELATIONS ---
            // nullable() permet de ne pas avoir de catégorie liée
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null')
                  ->comment('Lien optionnel vers la catégorie (Marbre, Bois, etc.)');
            
            // --- INFORMATIONS DE BASE ---
            $table->string('name')->unique()->comment('Nom du matériau');
            $table->string('slug')->unique()->comment('Slug pour les URLs (ex: granit-noir)');
            $table->text('description')->nullable();
            
            // --- VISUELS ---
            $table->string('image_url')->nullable()->comment('Chemin vers le fichier image sur le disque');
            $table->string('color')->nullable()->comment('Code couleur ou libellé de couleur');
            
            // --- TARIFICATION & OPTIONS TECHNIQUES ---
            // nullable() car le prix n'est pas forcément connu ou fixe à la création
            $table->decimal('price_per_sq_meter', 10, 2)
                  ->nullable()
                  ->comment('Prix au m² (optionnel)');
            
            $table->string('thickness_options')
                  ->nullable()
                  ->comment('Épaisseurs disponibles (ex: 2cm, 3cm)');
            
            // --- GESTION & ÉTAT ---
            $table->boolean('is_active')->default(true)->comment('Définit si le matériau est visible côté client');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Désactivation temporaire des clés étrangères pour éviter les erreurs 1451 au refresh
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('materials');
        Schema::enableForeignKeyConstraints();
    }
};
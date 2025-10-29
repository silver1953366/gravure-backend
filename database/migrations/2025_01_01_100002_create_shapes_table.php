<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations (crée la table 'shapes').
     */
    public function up(): void
    {
        Schema::create('shapes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nom de la forme (ex: Plaque, Rond, Cœur)');
            $table->string('slug')->unique()->nullable()->comment('Slug pour URL ou référence rapide'); 
            $table->text('description')->nullable();
            $table->string('image_url')->nullable()->comment('Lien vers l\'image d\'illustration de la forme.');
            
            // CORRECTION: Ajout du champ d'activation du modèle
            $table->boolean('is_active')->default(true)->comment('Indique si la forme est disponible pour les devis.'); 
            
            $table->timestamps();
        });
    }

    /**
     * Annule les migrations (supprime la table 'shapes').
     */
    public function down(): void
    {
        Schema::dropIfExists('shapes');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations (crée la table 'categories').
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nom de la catégorie (ex: Funéraire, Signalétique)');
            $table->text('description')->nullable()->comment('Description de la catégorie.'); 
            
            // AJOUT: Le champ 'is_active' pour l'alignement sur le modèle
            $table->boolean('is_active')->default(true)->comment('Indique si la catégorie est visible/utilisable.'); 
            
            $table->timestamps();
        });
    }

    /**
     * Annule les migrations (supprime la table 'categories').
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration.
     */
    public function up(): void
    {
        Schema::create('carousels', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Titre principal de la slide');
            $table->string('subtitle')->nullable()->comment('Sous-titre ou texte de description');
            $table->string('image_url')->comment('Chemin relatif du fichier image stocké');
            
            // Point 5 : Lien de redirection (ex: /catalog ou /configurator)
            $table->string('link')->default('/catalog');
            
            // Point 4 : Nom affiché sur le bouton (récupéré dynamiquement)
            $table->string('category_name')->nullable()->comment('Texte personnalisé du bouton d\'action');
            
            // Point 1 : Gestion de l\'ordre (indexé pour un tri rapide)
            $table->integer('order')->default(1)->index(); 
            
            // Point 3 : Dimension personnalisée
            $table->integer('height')->default(480)->comment('Hauteur personnalisée de la slide en pixels');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Annuler la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('carousels');
    }
};
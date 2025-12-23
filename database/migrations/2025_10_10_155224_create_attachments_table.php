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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            
            /**
             * MODIFICATION CRUCIALE : 
             * 1. On rend 'quote_id' NULLABLE car au moment de l'upload, 
             * le devis n'existe pas encore en base de données.
             * 2. On retire 'constrained()' pour éviter l'erreur de clé étrangère
             * immédiate lors de l'envoi de l'ID temporaire (timestamp).
             */
            $table->unsignedBigInteger('quote_id')->nullable()->comment('ID du devis final');
            
            /**
             * AJOUT : temp_quote_id
             * Ce champ recevra le Date.now() / 1000 envoyé par Angular.
             * Il servira de "clé de réconciliation" lors de la création du devis.
             */
            $table->string('temp_quote_id')->nullable()->index()->comment('ID temporaire envoyé par le front');

            // Lien vers l'utilisateur (toujours requis pour la sécurité)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // --- Métadonnées du Fichier ---
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');

            $table->timestamps();

            // Indexation pour accélérer la recherche lors de la validation finale
            $table->index(['quote_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
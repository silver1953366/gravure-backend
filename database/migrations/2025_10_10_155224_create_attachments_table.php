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
            
            // Lien vers le devis auquel le fichier est rattaché
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            
            // Lien vers l'utilisateur qui a téléversé le fichier (pour la sécurité)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // --- Métadonnées du Fichier ---
            $table->string('original_name')->comment('Nom original du fichier du client');
            $table->string('stored_path')->comment('Chemin de stockage sécurisé dans Laravel (ex: private/attachments/fichier.png)');
            $table->string('mime_type')->comment('Type MIME du fichier (ex: image/png, application/dxf)');
            $table->unsignedBigInteger('size')->comment('Taille du fichier en octets');

            $table->timestamps();
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
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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('type')->index()->comment('Type de notification (ex: quote_updated, order_shipped)');
            $table->text('message');
            $table->boolean('is_read')->default(false);

            // AJOUT CRITIQUE: Colonnes pour la relation polymorphique
            $table->unsignedBigInteger('resource_id')->nullable()->comment('ID du Devis ou de la Commande concernée');
            $table->string('resource_type')->nullable()->comment('Nom de la classe du modèle (ex: App\Models\Quote)');
            
            // Indexation des deux champs pour les requêtes efficaces
            $table->index(['resource_id', 'resource_type']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
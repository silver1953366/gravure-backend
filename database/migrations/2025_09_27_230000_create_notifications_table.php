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
            
            // Destinataire de la notification
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Structure visuelle et contenu
            $table->string('type')->index()->comment('info, success, warning, error');
            $table->string('title')->nullable()->comment('Titre court de la notification');
            $table->text('message');
            $table->string('link')->nullable()->comment('Lien vers la ressource dans Angular');
            
            // État de lecture
            $table->boolean('is_read')->default(false)->index();

            /**
             * RELATION POLYMORPHIQUE
             * Crée automatiquement 'resource_id' (bigInt) et 'resource_type' (string)
             * Permet de lier à un Quote, une Order, ou tout autre futur modèle.
             */
            $table->nullableMorphs('resource');
            
            $table->timestamps();

            // Index supplémentaire pour la performance des listes par utilisateur
            $table->index(['user_id', 'created_at']);
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
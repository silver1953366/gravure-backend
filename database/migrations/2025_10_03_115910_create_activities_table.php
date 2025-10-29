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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            // L'utilisateur qui a fait l'action. Nullable si l'action est faite par le système ou non-authentifiée.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); 
            $table->string('action'); // Type d'action (ex: quote_created)
            $table->string('model_type')->nullable(); // Modèle affecté (ex: App\Models\Quote)
            $table->unsignedBigInteger('model_id')->nullable(); // ID du modèle affecté
            $table->json('data_snapshot')->nullable(); // Données pertinentes ou changements
            $table->string('ip_address')->nullable(); // Adresse IP pour l'audit
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
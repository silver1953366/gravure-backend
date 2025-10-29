<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            
            // AJOUT 1: Le code que l'utilisateur saisit pour appliquer le rabais
            $table->string('code', 50)->unique()->comment('Code alphanumérique du rabais (ex: ETE2024)');
            
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2); 
            $table->decimal('min_order_amount', 12, 2)->default(0); 
            $table->boolean('is_active')->default(true);
            
            // AJOUT 2: Date d'expiration
            $table->timestamp('expires_at')->nullable()->comment('Date et heure d\'expiration du rabais');
            
            $table->timestamps();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
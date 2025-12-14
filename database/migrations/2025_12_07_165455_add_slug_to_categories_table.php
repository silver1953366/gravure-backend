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
        // 🚨 VÉRIFIEZ : La fonction up() doit ajouter la colonne 'slug'.
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La fonction down() doit supprimer la colonne 'slug'.
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
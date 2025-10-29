<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // --- 1. Correction de l'erreur 'duplicate column name: reference' ---
            // On ajoute 'reference' UNIQUEMENT si elle n'existe pas déjà (essentiel pour RefreshDatabase)
            if (!Schema::hasColumn('orders', 'reference')) {
                $table->string('reference')->nullable()->after('quote_id'); 
            }
            
            // --- 2. Autres champs que vous souhaitez ajouter ---
            
            // Spécifications du produit (copiées depuis le devis)
            // On vérifie aussi l'existence pour les foreign keys et autres colonnes non critiques,
            // car parfois des versions antérieures de la base de données peuvent les avoir.
            if (!Schema::hasColumn('orders', 'material_id')) {
                $table->foreignId('material_id')->nullable()->constrained('materials')->onDelete('set null')->after('quote_id');
            }
            
            if (!Schema::hasColumn('orders', 'shape_id')) {
                $table->foreignId('shape_id')->nullable()->constrained('shapes')->onDelete('set null');
            }

            if (!Schema::hasColumn('orders', 'material_dimension_id')) {
                $table->foreignId('material_dimension_id')->nullable()->constrained('material_dimensions')->onDelete('set null');
            }

            if (!Schema::hasColumn('orders', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1);
            }
            
            // Détails au moment de l'achat
            if (!Schema::hasColumn('orders', 'client_details')) {
                $table->json('client_details')->nullable()->comment('Détails spécifiques du client au moment de l\'achat');
            }
            
            if (!Schema::hasColumn('orders', 'details_snapshot')) {
                $table->json('details_snapshot')->nullable()->comment('Snapshot de la configuration du devis (pour l\'historique)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // IMPORTANT : On vérifie l'existence des colonnes avant de les supprimer
            // et on s'occupe des clés étrangères avant des colonnes.
            
            if (Schema::hasColumn('orders', 'material_id')) {
                $table->dropForeign(['material_id']);
            }
            if (Schema::hasColumn('orders', 'shape_id')) {
                $table->dropForeign(['shape_id']);
            }
            if (Schema::hasColumn('orders', 'material_dimension_id')) {
                $table->dropForeign(['material_dimension_id']);
            }
            
            $columnsToDrop = ['reference', 'material_id', 'shape_id', 'material_dimension_id', 'quantity', 'client_details', 'details_snapshot'];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

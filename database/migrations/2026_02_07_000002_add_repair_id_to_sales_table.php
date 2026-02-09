<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter repair_id à la table sales pour lier les ventes de pièces aux réparations
 * Les pièces de rechange vendues lors d'une réparation seront comptabilisées dans le CA Ventes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('repair_id')->nullable()->after('reseller_id')
                  ->constrained()->nullOnDelete();
            $table->boolean('is_repair_parts')->default(false)->after('repair_id');
            
            $table->index('repair_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['repair_id']);
            $table->dropColumn(['repair_id', 'is_repair_parts']);
        });
    }
};

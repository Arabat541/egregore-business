<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour restructurer les prix des produits
 * - Prix normal: 1-2 pièces (clients réparation ou achat unitaire)
 * - Prix demi-gros: 3-9 pièces (revendeurs/réparateurs externes)
 * - Prix de gros: 10+ pièces (revendeurs/réparateurs externes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Renommer minimum_price en normal_price (prix pour 1-2 pièces)
            $table->renameColumn('minimum_price', 'normal_price');
        });

        Schema::table('products', function (Blueprint $table) {
            // Ajouter les nouveaux prix
            $table->decimal('semi_wholesale_price', 12, 2)->nullable()->after('normal_price')
                ->comment('Prix demi-gros (3-9 pièces)');
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('semi_wholesale_price')
                ->comment('Prix de gros (10+ pièces)');
            
            // Renommer reseller_price pour plus de clarté (optionnel - peut être supprimé)
            // On garde reseller_price pour rétrocompatibilité mais il sera obsolète
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['semi_wholesale_price', 'wholesale_price']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('normal_price', 'minimum_price');
        });
    }
};

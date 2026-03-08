<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changer la contrainte unique du SKU pour être unique par boutique (shop_id + sku)
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Supprimer l'index unique global sur SKU
            $table->dropUnique(['sku']);
            
            // Créer un index unique composite (shop_id + sku)
            // Un même SKU peut exister dans différentes boutiques
            $table->unique(['shop_id', 'sku'], 'products_shop_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Supprimer l'index composite
            $table->dropUnique('products_shop_sku_unique');
            
            // Remettre l'index unique global
            $table->unique('sku');
        });
    }
};

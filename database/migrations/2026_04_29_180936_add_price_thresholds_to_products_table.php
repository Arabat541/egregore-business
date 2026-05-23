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
        Schema::table('products', function (Blueprint $table) {
            // Seuils de quantité configurables par produit
            $table->unsignedSmallInteger('qty_semi_wholesale_min')->default(3)->after('wholesale_price')
                  ->comment('Qté min pour le prix demi-gros (défaut 3)');
            $table->unsignedSmallInteger('qty_wholesale_min')->default(10)->after('qty_semi_wholesale_min')
                  ->comment('Qté min pour le prix de gros (défaut 10)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['qty_semi_wholesale_min', 'qty_wholesale_min']);
        });
    }
};

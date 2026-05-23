<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute un token de confirmation UUID aux commandes en ligne.
 * Ce token est requis pour accéder à la page de confirmation publique,
 * ce qui empêche l'énumération des commandes par numéro séquentiel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->string('confirmation_token', 64)->nullable()->unique()->after('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            $table->dropColumn('confirmation_token');
        });
    }
};

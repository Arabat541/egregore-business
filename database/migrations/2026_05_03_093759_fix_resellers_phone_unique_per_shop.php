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
        Schema::table('resellers', function (Blueprint $table) {
            // Supprimer la contrainte unique globale sur phone
            $table->dropUnique(['phone']);
            // Ajouter une contrainte unique composite (phone, shop_id)
            $table->unique(['phone', 'shop_id'], 'resellers_phone_shop_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropUnique('resellers_phone_shop_unique');
            $table->unique('phone');
        });
    }
};

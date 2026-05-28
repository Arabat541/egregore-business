<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'index unique sur `key` seul empêchait d'avoir plusieurs boutiques
     * avec le même paramètre (ex: technician_labor_share par boutique).
     * L'index composite (key, shop_id) ajouté précédemment est suffisant.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unique('key', 'settings_key_unique');
        });
    }
};

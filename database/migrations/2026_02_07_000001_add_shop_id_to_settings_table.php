<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajouter shop_id aux paramètres pour permettre des paramètres par boutique
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')->constrained('shops')->nullOnDelete();
            $table->boolean('is_global')->default(true)->after('shop_id');
            
            // Modifier l'index unique pour permettre la même clé par boutique
            // On doit d'abord supprimer l'index existant s'il existe
        });

        // Créer un index unique composite (key + shop_id)
        Schema::table('settings', function (Blueprint $table) {
            $table->unique(['key', 'shop_id'], 'settings_key_shop_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_shop_unique');
            $table->dropForeign(['shop_id']);
            $table->dropColumn(['shop_id', 'is_global']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajuster la table des bonus de fidélité
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_loyalty_bonuses', function (Blueprint $table) {
            // Renommer et ajouter des colonnes
            $table->renameColumn('total_purchases', 'yearly_purchases');
            $table->renameColumn('user_id', 'paid_by');
            $table->renameColumn('payment_type', 'payment_method');
            
            // Ajouter la colonne tier
            $table->string('tier', 20)->default('bronze')->after('yearly_purchases');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_loyalty_bonuses', function (Blueprint $table) {
            $table->renameColumn('yearly_purchases', 'total_purchases');
            $table->renameColumn('paid_by', 'user_id');
            $table->renameColumn('payment_method', 'payment_type');
            $table->dropColumn('tier');
        });
    }
};

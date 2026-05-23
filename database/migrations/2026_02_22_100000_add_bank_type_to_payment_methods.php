<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute le type 'bank' aux méthodes de paiement pour les virements bancaires
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payment_methods MODIFY COLUMN type ENUM('cash', 'mobile_money', 'card', 'bank')");
        }
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('type', 'bank')->delete();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payment_methods MODIFY COLUMN type ENUM('cash', 'mobile_money', 'card')");
        }
    }
};

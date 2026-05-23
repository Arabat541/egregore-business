<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convertir ENUM en VARCHAR pour plus de flexibilité
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE reseller_payments MODIFY COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE reseller_payments MODIFY COLUMN payment_method ENUM('cash', 'mobile_money', 'card') NOT NULL DEFAULT 'cash'");
        }
    }
};

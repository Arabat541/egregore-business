<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Ajouter 'cancelled' à sales.payment_status
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_status
            ENUM('paid', 'partial', 'credit', 'cancelled') NOT NULL DEFAULT 'paid'");

        // Ajouter 'repair_cancel' à stock_movements.type
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type
            ENUM('entry','exit','adjustment','repair_usage','return',
                 'inventory','transfer_in','transfer_out','purchase',
                 'sale','sale_cancel','repair_cancel','loss') NOT NULL DEFAULT 'entry'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_status
            ENUM('paid', 'partial', 'credit') NOT NULL DEFAULT 'paid'");

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type
            ENUM('entry','exit','adjustment','repair_usage','return',
                 'inventory','transfer_in','transfer_out','purchase',
                 'sale','sale_cancel','loss') NOT NULL DEFAULT 'entry'");
    }
};

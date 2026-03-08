<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'enum pour inclure tous les types de mouvements
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
            'entry',
            'exit', 
            'adjustment',
            'repair_usage',
            'return',
            'inventory',
            'transfer_in',
            'transfer_out',
            'purchase',
            'sale',
            'sale_cancel',
            'loss'
        ) NOT NULL DEFAULT 'entry'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
            'entry',
            'exit',
            'adjustment',
            'repair_usage',
            'return'
        ) NOT NULL DEFAULT 'entry'");
    }
};

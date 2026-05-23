<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Ajouter le type 'return' aux mouvements de stock
 * Pour gérer les retours de produits par les revendeurs
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('entry', 'exit', 'adjustment', 'repair_usage', 'return') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('entry', 'exit', 'adjustment', 'repair_usage') NOT NULL");
        }
    }
};

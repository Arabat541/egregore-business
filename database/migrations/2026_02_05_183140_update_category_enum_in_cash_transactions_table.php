<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convertir category de ENUM vers VARCHAR pour plus de flexibilité
     */
    public function up(): void
    {
        // MySQL: modifier ENUM en VARCHAR
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN category VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer l'ENUM original (optionnel)
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN category ENUM('sale', 'repair', 'debt_payment', 'expense', 'adjustment', 'sav_refund') NOT NULL");
    }
};

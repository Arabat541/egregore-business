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
        // Modifier directement avec SQL pour éviter les problèmes de doctrine/dbal
        DB::statement("ALTER TABLE cash_transactions MODIFY payment_method VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour à ENUM (optionnel)
        DB::statement("ALTER TABLE cash_transactions MODIFY payment_method ENUM('cash', 'mobile_money', 'card', 'check', 'transfer', 'mixed') NULL");
    }
};

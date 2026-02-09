<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convertir payment_method de ENUM vers VARCHAR pour plus de flexibilité
     */
    public function up(): void
    {
        // MySQL: modifier directement la colonne ENUM vers VARCHAR
        DB::statement("ALTER TABLE repairs MODIFY payment_method VARCHAR(50) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir à l'ENUM original (optionnel)
        DB::statement("ALTER TABLE repairs MODIFY payment_method ENUM('cash', 'mobile_money', 'card', 'mixed') NULL");
    }
};

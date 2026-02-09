<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter sale_id à repair_parts pour lier chaque pièce à sa vente
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_parts', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('total_cost')
                  ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('repair_parts', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->decimal('labor_cost', 12, 2)->default(0)->after('final_cost'); // Main d'œuvre
            $table->decimal('parts_cost', 12, 2)->default(0)->after('labor_cost'); // Coût des pièces
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['labor_cost', 'parts_cost']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cash_registers', 'shop_id')) {
            return; // Already added manually on production
        }

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Backfill shop_id from the linked user
        DB::statement('
            UPDATE cash_registers cr
            JOIN users u ON u.id = cr.user_id
            SET cr.shop_id = u.shop_id
            WHERE cr.shop_id IS NULL
        ');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('cash_registers', 'shop_id')) {
            return;
        }

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });
    }
};

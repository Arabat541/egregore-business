<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stock_movements', 'shop_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('shop_id')
                      ->nullable()
                      ->after('id')
                      ->constrained()
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'shop_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->dropColumn('shop_id');
            });
        }
    }
};

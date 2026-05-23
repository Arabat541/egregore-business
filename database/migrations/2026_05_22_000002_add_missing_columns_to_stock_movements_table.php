<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->nullable()->after('reason');
            }
            if (!Schema::hasColumn('stock_movements', 'notes')) {
                $table->text('notes')->nullable()->after('unit_cost');
            }
            if (!Schema::hasColumn('stock_movements', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_movements MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('stock_movements', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
            if (Schema::hasColumn('stock_movements', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE stock_movements MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');
        }
    }
};

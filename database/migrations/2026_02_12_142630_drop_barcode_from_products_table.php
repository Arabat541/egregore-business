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
        if (!Schema::hasColumn('products', 'barcode')) {
            return;
        }

        // Drop indexes before column (SQLite cannot drop a column referenced by an index)
        $indexNames = collect(Schema::getIndexes('products'))->pluck('name');
        Schema::table('products', function (Blueprint $table) use ($indexNames) {
            if ($indexNames->contains('products_barcode_index')) {
                $table->dropIndex('products_barcode_index');
            }
            if ($indexNames->contains('products_barcode_unique')) {
                $table->dropUnique('products_barcode_unique');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->unique()->after('sku');
        });
    }
};

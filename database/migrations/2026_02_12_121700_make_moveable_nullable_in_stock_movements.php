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
        Schema::table('stock_movements', function (Blueprint $table) {
            // Rendre moveable_type et moveable_id nullable
            $table->string('moveable_type')->nullable()->change();
            $table->unsignedBigInteger('moveable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('moveable_type')->nullable(false)->change();
            $table->unsignedBigInteger('moveable_id')->nullable(false)->change();
        });
    }
};

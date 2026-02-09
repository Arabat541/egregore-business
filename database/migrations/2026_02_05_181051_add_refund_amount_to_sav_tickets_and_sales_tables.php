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
        // Ajouter refund_amount à sav_tickets (si pas déjà existant)
        if (!Schema::hasColumn('sav_tickets', 'refund_amount')) {
            Schema::table('sav_tickets', function (Blueprint $table) {
                $table->decimal('refund_amount', 12, 2)->default(0)->after('quantity_returned');
            });
        }

        // Ajouter refund_amount à sales
        if (!Schema::hasColumn('sales', 'refund_amount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('refund_amount', 12, 2)->default(0)->after('total_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sav_tickets', 'refund_amount')) {
            Schema::table('sav_tickets', function (Blueprint $table) {
                $table->dropColumn('refund_amount');
            });
        }

        if (Schema::hasColumn('sales', 'refund_amount')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('refund_amount');
            });
        }
    }
};

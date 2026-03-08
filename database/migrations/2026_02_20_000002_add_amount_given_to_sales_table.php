<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter le champ amount_given pour stocker le montant donné par le client
 * Cela permet de calculer la monnaie à rendre (amount_given - total_amount)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_given', 12, 2)->nullable()->after('amount_paid')
                ->comment('Montant physiquement donné par le client');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('amount_given');
        });
    }
};

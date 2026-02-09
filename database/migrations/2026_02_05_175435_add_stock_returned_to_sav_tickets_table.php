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
        Schema::table('sav_tickets', function (Blueprint $table) {
            // Indique si le produit a été retourné en stock
            $table->boolean('stock_returned')->default(false)->after('resolution_type');
            // Date du retour en stock
            $table->timestamp('stock_returned_at')->nullable()->after('stock_returned');
            // Utilisateur qui a effectué le retour
            $table->foreignId('stock_returned_by')->nullable()->after('stock_returned_at')
                  ->constrained('users')->nullOnDelete();
            // Quantité retournée (pour les échanges partiels)
            $table->integer('quantity_returned')->default(0)->after('stock_returned_by');
            // Notes sur le retour
            $table->text('return_notes')->nullable()->after('quantity_returned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sav_tickets', function (Blueprint $table) {
            $table->dropForeign(['stock_returned_by']);
            $table->dropColumn([
                'stock_returned',
                'stock_returned_at',
                'stock_returned_by',
                'quantity_returned',
                'return_notes'
            ]);
        });
    }
};

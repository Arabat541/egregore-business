<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les transactions de caisse
 * Toutes les entrées et sorties d'argent
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['income', 'expense']); // Entrée ou sortie
            $table->enum('category', [
                'sale',           // Vente
                'repair',         // Réparation
                'debt_payment',   // Paiement créance revendeur
                'expense',        // Dépense
                'adjustment'      // Ajustement
            ]);
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'mobile_money', 'card']);
            $table->morphs('transactionable'); // Polymorphic: Sale, Repair, ResellerPayment
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['cash_register_id', 'type']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};

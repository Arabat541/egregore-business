<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les paiements des revendeurs (remboursement de crédit)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Caissière qui a encaissé
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete(); // Vente concernée
            $table->decimal('amount', 12, 2);
            $table->decimal('debt_before', 12, 2); // Dette avant paiement
            $table->decimal('debt_after', 12, 2); // Dette après paiement
            $table->enum('payment_method', ['cash', 'mobile_money', 'card']);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_payments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les ventes
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Numéro de facture
            $table->foreignId('user_id')->constrained(); // Caissière qui a fait la vente
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // Client particulier
            $table->foreignId('reseller_id')->nullable()->constrained()->nullOnDelete(); // Revendeur
            $table->enum('client_type', ['customer', 'reseller']); // Type de client
            $table->decimal('subtotal', 12, 2); // Sous-total
            $table->decimal('discount_amount', 12, 2)->default(0); // Remise
            $table->decimal('tax_amount', 12, 2)->default(0); // TVA
            $table->decimal('total_amount', 12, 2); // Total
            $table->decimal('amount_paid', 12, 2)->default(0); // Montant payé
            $table->decimal('amount_due', 12, 2)->default(0); // Reste à payer (pour crédit revendeur)
            $table->enum('payment_status', ['paid', 'partial', 'credit'])->default('paid');
            $table->enum('payment_method', ['cash', 'mobile_money', 'card', 'mixed'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index(['client_type', 'created_at']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

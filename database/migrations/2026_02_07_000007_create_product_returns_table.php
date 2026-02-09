<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pour les retours de produits par les revendeurs
 * Cas d'usage: Un revendeur paie sa dette partiellement en espèces + retour de produits non vendus
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('reseller_payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Qui a réceptionné
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            
            $table->integer('quantity'); // Quantité retournée
            $table->decimal('unit_price', 10, 2); // Prix unitaire (prix de la vente originale)
            $table->decimal('total_value', 10, 2); // Valeur totale du retour
            
            $table->enum('condition', ['new', 'good', 'damaged'])->default('good'); // État du produit
            $table->boolean('restock', )->default(true); // Remettre en stock ?
            
            $table->text('reason')->nullable(); // Raison du retour
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['reseller_id', 'created_at']);
            $table->index('product_id');
        });

        // Ajouter des colonnes au paiement revendeur pour tracking
        Schema::table('reseller_payments', function (Blueprint $table) {
            $table->decimal('cash_amount', 10, 2)->default(0)->after('amount'); // Montant en espèces
            $table->decimal('return_amount', 10, 2)->default(0)->after('cash_amount'); // Valeur des retours
            $table->boolean('has_product_return')->default(false)->after('return_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_payments', function (Blueprint $table) {
            $table->dropColumn(['cash_amount', 'return_amount', 'has_product_return']);
        });
        
        Schema::dropIfExists('product_returns');
    }
};

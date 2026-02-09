<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pour l'historique des prix fournisseurs par produit
 * Permet de comparer les prix et choisir le fournisseur le moins cher
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table pivot produit-fournisseur avec historique des prix
        Schema::create('supplier_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('unit_price', 12, 2); // Prix unitaire actuel
            $table->decimal('last_price', 12, 2)->nullable(); // Prix précédent pour comparaison
            $table->string('currency', 10)->default('XOF');
            $table->integer('min_order_quantity')->default(1); // Quantité minimum de commande
            $table->integer('lead_time_days')->nullable(); // Délai de livraison en jours
            $table->text('notes')->nullable();
            $table->timestamp('price_updated_at')->nullable(); // Date dernière mise à jour prix
            $table->timestamps();

            // Un produit ne peut avoir qu'un seul prix actif par fournisseur
            $table->unique(['supplier_id', 'product_id']);
        });

        // Table historique complet des prix (pour analyse des tendances)
        Schema::create('supplier_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('unit_price', 12, 2);
            $table->string('currency', 10)->default('XOF');
            $table->foreignId('supplier_order_id')->nullable()->constrained()->onDelete('set null'); // Lié à quelle commande
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['product_id', 'recorded_at']);
            $table->index(['supplier_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_price_history');
        Schema::dropIfExists('supplier_product_prices');
    }
};

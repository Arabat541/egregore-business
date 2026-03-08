<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les produits (téléphones, accessoires, pièces)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode')->unique()->nullable(); // Code-barres pour scanner
            $table->string('sku')->unique()->nullable(); // Référence interne
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0); // Prix d'achat
            $table->decimal('selling_price', 12, 2); // Prix de vente particuliers
            $table->decimal('reseller_price', 12, 2)->nullable(); // Prix revendeurs
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('stock_alert_threshold')->default(5); // Seuil alerte stock faible
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->enum('type', ['phone', 'accessory', 'spare_part'])->default('accessory');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('barcode');
            $table->index('sku');
            $table->index('name');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

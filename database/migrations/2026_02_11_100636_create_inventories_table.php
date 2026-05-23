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
        // Table des inventaires
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Qui fait l'inventaire
            $table->string('reference')->unique(); // INV-2026-001
            $table->enum('status', ['in_progress', 'completed', 'validated', 'cancelled'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('total_products')->default(0);
            $table->integer('products_with_difference')->default(0);
            $table->decimal('total_difference_value', 15, 2)->default(0); // Valeur des écarts
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Table des articles d'inventaire
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('theoretical_quantity'); // Stock système au moment de l'inventaire
            $table->integer('physical_quantity')->nullable(); // Quantité comptée
            $table->integer('difference')->nullable(); // Écart (physique - théorique)
            $table->decimal('difference_value', 12, 2)->nullable(); // Valeur de l'écart
            $table->text('notes')->nullable();
            $table->boolean('counted')->default(false); // Si le produit a été compté
            $table->timestamps();

            $table->unique(['inventory_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventories');
    }
};

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
        // Table des transferts de stock
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('from_shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('to_shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Créé par
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('validated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['from_shop_id', 'status']);
            $table->index(['to_shop_id', 'status']);
            $table->index('created_at');
        });

        // Table des articles de transfert
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('purchase_price', 12, 2)->default(0); // Prix d'achat au moment du transfert
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');

            // Client
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_city')->nullable();

            // Montants
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Statut
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'ready',
                'shipped',
                'delivered',
                'cancelled',
            ])->default('pending');

            $table->enum('payment_method', ['cash_on_delivery', 'mobile_money', 'bank_transfer'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->enum('delivery_method', ['pickup', 'delivery'])->default('pickup');

            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('order_number');
            $table->index('customer_phone');
        });

        Schema::create('online_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_items');
        Schema::dropIfExists('online_orders');
    }
};

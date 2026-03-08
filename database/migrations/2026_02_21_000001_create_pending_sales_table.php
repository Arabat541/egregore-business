<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Créé par
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->date('sale_date'); // Date de la vente (aujourd'hui généralement)
            $table->enum('status', ['pending', 'validated', 'cancelled'])->default('pending');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete(); // Lien vers la vente finale
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour recherche rapide des ventes en attente par revendeur et date
            $table->index(['reseller_id', 'sale_date', 'status']);
            $table->index(['shop_id', 'status']);
        });

        Schema::create('pending_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pending_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_sale_items');
        Schema::dropIfExists('pending_sales');
    }
};

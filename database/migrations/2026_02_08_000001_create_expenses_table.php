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
        // Table des catégories de dépenses (doit être créée avant expenses)
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Loyer, Électricité, Transport, Fournitures, etc.
            $table->string('icon')->nullable(); // Icône FontAwesome
            $table->string('color')->default('#6c757d'); // Couleur pour affichage
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(false); // Nécessite approbation admin
            $table->decimal('monthly_budget', 12, 2)->nullable(); // Budget mensuel optionnel
            $table->timestamps();
            
            $table->unique(['shop_id', 'name']);
        });

        // Table des dépenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Qui a enregistré
            $table->foreignId('cash_register_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('expense_category_id')->constrained()->onDelete('restrict');
            
            $table->string('reference')->unique(); // DEP-20260208-001
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->string('beneficiary')->nullable(); // Bénéficiaire (propriétaire, fournisseur, etc.)
            $table->date('expense_date'); // Date de la dépense
            $table->string('receipt_number')->nullable(); // Numéro de reçu/facture
            $table->string('receipt_image')->nullable(); // Photo du reçu
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'check'])->default('cash');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved'); // Pour validation admin si nécessaire
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['shop_id', 'expense_date']);
            $table->index(['shop_id', 'expense_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};

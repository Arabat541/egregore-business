<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajouter le système de fidélité aux revendeurs
 * - Points de fidélité accumulés
 * - Bonus annuels
 */
return new class extends Migration
{
    public function up(): void
    {
        // Champs de fidélité sur les revendeurs
        Schema::table('resellers', function (Blueprint $table) {
            $table->decimal('total_purchases_year', 15, 2)->default(0)->after('current_debt');
            $table->decimal('loyalty_points', 10, 2)->default(0)->after('total_purchases_year');
            $table->decimal('loyalty_bonus_rate', 5, 2)->default(0)->after('loyalty_points'); // % de bonus
            $table->date('loyalty_year_start')->nullable()->after('loyalty_bonus_rate');
        });

        // Table pour l'historique des bonus de fidélité
        Schema::create('reseller_loyalty_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('year'); // Année civile du bonus
            $table->decimal('total_purchases', 15, 2); // Total achats de l'année
            $table->decimal('bonus_rate', 5, 2); // Taux appliqué
            $table->decimal('bonus_amount', 15, 2); // Montant du bonus
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->enum('payment_type', ['cash', 'credit', 'discount'])->default('credit'); // Comment le bonus est accordé
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['reseller_id', 'year']); // Un seul bonus par an par revendeur
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_loyalty_bonuses');

        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn([
                'total_purchases_year',
                'loyalty_points',
                'loyalty_bonus_rate',
                'loyalty_year_start',
            ]);
        });
    }
};

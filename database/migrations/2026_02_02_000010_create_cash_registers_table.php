<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la gestion de la caisse
 * Ouverture et clôture quotidienne
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Caissière
            $table->date('date');
            $table->decimal('opening_balance', 12, 2); // Fond de caisse à l'ouverture
            $table->decimal('closing_balance', 12, 2)->nullable(); // Solde à la clôture
            $table->decimal('expected_balance', 12, 2)->nullable(); // Solde théorique
            $table->decimal('difference', 12, 2)->nullable(); // Écart
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};

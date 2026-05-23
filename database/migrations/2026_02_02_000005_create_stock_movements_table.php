<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour l'historique des mouvements de stock
 * Traçabilité complète des entrées/sorties
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Qui a fait le mouvement
            $table->enum('type', ['entry', 'exit', 'adjustment', 'repair_usage']); // Type de mouvement
            $table->integer('quantity'); // Positif = entrée, Négatif = sortie
            $table->integer('quantity_before'); // Stock avant mouvement
            $table->integer('quantity_after'); // Stock après mouvement
            $table->string('reference')->nullable(); // Référence (vente, réparation, etc.)
            $table->morphs('moveable'); // Polymorphic: Sale, Repair, etc.
            $table->text('reason')->nullable(); // Motif du mouvement
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les pièces utilisées dans les réparations
 * Permet le suivi des pièces consommées et la sortie automatique du stock
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Pièce détachée
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2); // Coût unitaire
            $table->decimal('total_cost', 12, 2); // Coût total
            $table->timestamps();

            $table->index('repair_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_parts');
    }
};

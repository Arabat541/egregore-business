<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pour suivre les pièces remplacées lors d'un SAV réparation
 * Quand une pièce est défectueuse et remplacée sous garantie,
 * le coût est déduit du CA du technicien qui a fait la réparation initiale
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sav_replaced_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sav_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_repair_part_id')->nullable()->constrained('repair_parts')->nullOnDelete();
            $table->foreignId('defective_product_id')->constrained('products');
            $table->foreignId('replacement_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('technician_id')->constrained('users'); // Le technicien qui a fait la réparation initiale
            $table->integer('quantity')->default(1);
            $table->decimal('defective_part_cost', 10, 2); // Coût de la pièce défectueuse à déduire
            $table->decimal('replacement_part_cost', 10, 2)->nullable(); // Coût de la nouvelle pièce
            $table->string('reason')->nullable(); // Raison du remplacement
            $table->boolean('ca_deducted')->default(false); // Si la déduction CA a été appliquée
            $table->timestamp('deducted_at')->nullable();
            $table->foreignId('deducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sav_replaced_parts');
    }
};

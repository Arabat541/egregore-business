<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la table des revendeurs
 * Les revendeurs peuvent acheter à crédit avec un plafond défini par l'admin
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable(); // Numéro fiscal
            $table->decimal('credit_limit', 12, 2)->default(0); // Plafond de crédit (paramétré par admin)
            $table->decimal('current_debt', 12, 2)->default(0); // Dette actuelle
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('credit_allowed')->default(false); // Crédit autorisé ou non
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};

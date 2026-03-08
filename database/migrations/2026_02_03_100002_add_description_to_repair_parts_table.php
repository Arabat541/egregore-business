<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout de la description pour permettre les pièces manuelles (non en stock)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_parts', function (Blueprint $table) {
            // Rendre product_id nullable pour permettre les pièces manuelles
            $table->foreignId('product_id')->nullable()->change();
            
            // Ajouter une description pour les pièces manuelles
            $table->string('description')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('repair_parts', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};

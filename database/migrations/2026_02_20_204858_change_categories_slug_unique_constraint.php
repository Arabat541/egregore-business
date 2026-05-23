<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte unique sur slug seul
            $table->dropUnique(['slug']);
            
            // Ajouter une contrainte unique sur slug + shop_id
            // Permet le même slug dans différentes boutiques
            $table->unique(['slug', 'shop_id'], 'categories_slug_shop_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_shop_unique');
            $table->unique('slug');
        });
    }
};
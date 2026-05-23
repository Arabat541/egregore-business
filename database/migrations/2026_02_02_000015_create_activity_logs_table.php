<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour l'historique des actions utilisateurs
 * Traçabilité et audit
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // create, update, delete, login, logout, etc.
            $table->string('model_type')->nullable(); // Type du modèle concerné
            $table->unsignedBigInteger('model_id')->nullable(); // ID du modèle concerné
            $table->json('old_values')->nullable(); // Anciennes valeurs
            $table->json('new_values')->nullable(); // Nouvelles valeurs
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

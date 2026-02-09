<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table pour les notifications internes du système
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Destinataire
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Type de notification
            $table->string('type'); // repair_ready, stock_low, sav_urgent, payment_received, etc.
            
            // Contenu
            $table->string('title');
            $table->text('message');
            $table->string('icon')->default('bi-bell'); // Icône Bootstrap
            $table->string('color')->default('primary'); // primary, success, warning, danger, info
            
            // Lien vers l'élément concerné
            $table->string('link')->nullable(); // URL vers la page concernée
            $table->string('notifiable_type')->nullable(); // Modèle concerné (Repair, Sale, etc.)
            $table->unsignedBigInteger('notifiable_id')->nullable(); // ID du modèle
            
            // Statut
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_important')->default(false);
            $table->boolean('play_sound')->default(false);
            
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les tickets S.A.V (Service Après-Vente)
 * Gestion des retours, échanges, garanties et réclamations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sav_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // Numéro de ticket SAV
            
            // Références
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete(); // Vente concernée
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); // Produit concerné
            $table->foreignId('created_by')->constrained('users'); // Qui a créé le ticket
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Assigné à
            
            // Type de demande
            $table->enum('type', [
                'return',           // Retour produit
                'exchange',         // Échange
                'warranty',         // Garantie
                'complaint',        // Réclamation
                'refund',           // Remboursement
                'other'             // Autre
            ]);
            
            // Informations produit (si pas lié à une vente)
            $table->string('product_name')->nullable();
            $table->string('product_serial')->nullable();
            $table->date('purchase_date')->nullable();
            
            // Description du problème
            $table->text('issue_description'); // Description du problème
            $table->text('customer_request')->nullable(); // Demande du client
            
            // Statut
            $table->enum('status', [
                'open',             // Ouvert
                'in_progress',      // En cours de traitement
                'waiting_customer', // En attente client
                'waiting_parts',    // En attente pièces
                'resolved',         // Résolu
                'closed',           // Fermé
                'rejected'          // Rejeté
            ])->default('open');
            
            // Priorité
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Résolution
            $table->text('resolution_notes')->nullable(); // Notes de résolution
            $table->enum('resolution_type', [
                'repaired',         // Réparé
                'exchanged',        // Échangé
                'refunded',         // Remboursé
                'rejected',         // Rejeté
                'no_action',        // Aucune action nécessaire
                'other'             // Autre
            ])->nullable();
            
            // Financier
            $table->decimal('refund_amount', 12, 2)->default(0); // Montant remboursé
            $table->decimal('exchange_difference', 12, 2)->default(0); // Différence échange
            
            // Dates
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_number');
            $table->index('status');
            $table->index('type');
            $table->index(['customer_id', 'status']);
        });

        // Table pour les commentaires/suivis du ticket
        Schema::create('sav_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sav_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('comment');
            $table->boolean('is_internal')->default(false); // Commentaire interne ou visible client
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sav_ticket_comments');
        Schema::dropIfExists('sav_tickets');
    }
};

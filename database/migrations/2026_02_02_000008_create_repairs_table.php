<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour les réparations
 * Workflow: Création → Paiement → Diagnostic → Réparation → Livraison
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('repair_number')->unique(); // Numéro de réparation
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users'); // Caissière qui a créé
            $table->foreignId('technician_id')->nullable()->constrained('users'); // Technicien assigné
            
            // Informations appareil
            $table->string('device_type'); // Type d'appareil (iPhone, Samsung, etc.)
            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('device_imei')->nullable();
            $table->string('device_password')->nullable(); // Code de déverrouillage (crypté)
            $table->text('device_condition')->nullable(); // État à la réception
            $table->json('accessories_received')->nullable(); // Accessoires laissés (chargeur, etc.)
            
            // Problème et diagnostic
            $table->text('reported_issue'); // Problème signalé par le client
            $table->text('diagnosis')->nullable(); // Diagnostic technicien
            $table->text('repair_notes')->nullable(); // Notes de réparation
            
            // Statuts
            $table->enum('status', [
                'pending_payment',      // En attente de paiement
                'paid_pending_diagnosis', // Payé, en attente diagnostic
                'in_diagnosis',         // En cours de diagnostic
                'waiting_parts',        // En attente de pièces
                'in_repair',            // En cours de réparation
                'repaired',             // Réparé
                'ready_for_pickup',     // Prêt pour retrait
                'delivered',            // Livré
                'cancelled'             // Annulé
            ])->default('pending_payment');
            
            // Financier
            $table->decimal('estimated_cost', 12, 2)->nullable(); // Coût estimé
            $table->decimal('final_cost', 12, 2)->nullable(); // Coût final
            $table->decimal('deposit_amount', 12, 2)->default(0); // Acompte versé
            $table->decimal('amount_paid', 12, 2)->default(0); // Total payé
            $table->enum('payment_method', ['cash', 'mobile_money', 'card', 'mixed'])->nullable();
            
            // Dates importantes
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('diagnosis_at')->nullable();
            $table->timestamp('repaired_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('repair_number');
            $table->index('status');
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};

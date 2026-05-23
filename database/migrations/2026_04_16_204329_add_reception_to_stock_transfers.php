<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Champs de réception pour les transferts inter-boutiques
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->timestamp('in_transit_at')->nullable()->after('validated_at');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete()->after('validated_by');
            $table->timestamp('received_at')->nullable()->after('in_transit_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete()->after('sent_by');
            $table->text('reception_notes')->nullable()->after('notes');
            $table->string('reception_status', 20)->nullable()->after('reception_notes'); // 'ok' | 'discrepancy'
        });

        // Quantité réellement reçue par article lors de la confirmation
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->integer('quantity_received')->nullable()->after('quantity');
        });

        // Notes de réception pour les factures fournisseur
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->text('reception_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['sent_by']);
            $table->dropForeign(['received_by']);
            $table->dropColumn(['in_transit_at', 'sent_by', 'received_at', 'received_by', 'reception_notes', 'reception_status']);
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn('quantity_received');
        });

        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn('reception_notes');
        });
    }
};

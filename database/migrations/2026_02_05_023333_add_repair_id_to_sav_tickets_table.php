<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute le support des réparations dans les tickets S.A.V.
     */
    public function up(): void
    {
        Schema::table('sav_tickets', function (Blueprint $table) {
            // Référence vers une réparation (pour garantie réparation)
            $table->foreignId('repair_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            
            // Ajouter le type 'repair_warranty' pour les garanties de réparation
            // Note: Pour modifier un ENUM, on doit recréer la colonne
        });
        
        // Modifier le type ENUM pour inclure repair_warranty
        DB::statement("ALTER TABLE sav_tickets MODIFY COLUMN type ENUM('return', 'exchange', 'warranty', 'repair_warranty', 'complaint', 'refund', 'other')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre le type ENUM original
        DB::statement("ALTER TABLE sav_tickets MODIFY COLUMN type ENUM('return', 'exchange', 'warranty', 'complaint', 'refund', 'other')");
        
        Schema::table('sav_tickets', function (Blueprint $table) {
            $table->dropForeign(['repair_id']);
            $table->dropColumn('repair_id');
        });
    }
};

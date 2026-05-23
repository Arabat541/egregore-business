<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend les revendeurs globaux : un seul enregistrement par réparateur,
 * indépendamment des boutiques. Les ventes gardent leur shop_id.
 *
 * Si des doublons existent (même téléphone dans plusieurs boutiques),
 * on garde le plus ancien (id le plus bas) et on re-pointe les FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Fusionner les doublons (même téléphone, boutiques différentes) ──
        $groups = DB::table('resellers')
            ->select('phone', DB::raw('MIN(id) as primary_id'), DB::raw('COUNT(*) as cnt'))
            ->whereNull('deleted_at')
            ->groupBy('phone')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $primaryId  = $group->primary_id;
            $duplicates = DB::table('resellers')
                ->where('phone', $group->phone)
                ->where('id', '!=', $primaryId)
                ->pluck('id');

            if ($duplicates->isEmpty()) {
                continue;
            }

            // Agréger les montants numériques sur le primaire
            $agg = DB::table('resellers')
                ->whereIn('id', $duplicates)
                ->selectRaw('
                    COALESCE(SUM(current_debt), 0)          as debt,
                    COALESCE(SUM(total_purchases_year), 0)  as purchases,
                    COALESCE(SUM(loyalty_points), 0)        as points
                ')
                ->first();

            DB::table('resellers')->where('id', $primaryId)->update([
                'current_debt'         => DB::raw("current_debt + {$agg->debt}"),
                'total_purchases_year' => DB::raw("total_purchases_year + {$agg->purchases}"),
                'loyalty_points'       => DB::raw("loyalty_points + {$agg->points}"),
            ]);

            // Re-pointer les clés étrangères vers le primaire
            DB::table('sales')
                ->whereIn('reseller_id', $duplicates)
                ->update(['reseller_id' => $primaryId]);

            DB::table('reseller_payments')
                ->whereIn('reseller_id', $duplicates)
                ->update(['reseller_id' => $primaryId]);

            DB::table('reseller_loyalty_bonuses')
                ->whereIn('reseller_id', $duplicates)
                ->update(['reseller_id' => $primaryId]);

            // Supprimer les doublons (force-delete, pas de soft-delete ici)
            DB::table('resellers')->whereIn('id', $duplicates)->delete();
        }

        // ── 2. Modifier le schéma ──
        Schema::table('resellers', function (Blueprint $table) {
            // Supprimer la contrainte unique composite (phone, shop_id)
            $table->dropUnique('resellers_phone_shop_unique');

            // Supprimer la FK puis la colonne shop_id
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');

            // Rétablir un unique global sur phone
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->foreignId('shop_id')->nullable()->after('id')->constrained('shops')->nullOnDelete();
            $table->unique(['phone', 'shop_id'], 'resellers_phone_shop_unique');
        });
    }
};

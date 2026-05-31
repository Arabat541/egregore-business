<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Reseller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ResellerMergeService
{
    /**
     * Fusionne plusieurs revendeurs dans un revendeur primaire.
     *
     * Toutes les ventes, paiements, retours, ventes en attente et bonus de
     * fidélité des doublons sont re-pointés vers le primaire. Les montants
     * cumulatifs (dette, achats de l'année, points) sont additionnés.
     * Les doublons sont ensuite soft-deleted.
     *
     * @param  int   $primaryId    ID du revendeur à conserver
     * @param  int[] $duplicateIds IDs des revendeurs à fusionner (≥ 1)
     */
    public function merge(int $primaryId, array $duplicateIds): Reseller
    {
        $duplicateIds = array_values(array_filter(
            array_unique($duplicateIds),
            fn($id) => $id !== $primaryId
        ));

        if (empty($duplicateIds)) {
            throw new \InvalidArgumentException('Au moins un doublon est requis.');
        }

        return DB::transaction(function () use ($primaryId, $duplicateIds): Reseller {
            $primary = Reseller::findOrFail($primaryId);

            // Agréger les montants cumulatifs des doublons
            $agg = DB::table('resellers')
                ->whereIn('id', $duplicateIds)
                ->selectRaw('
                    COALESCE(SUM(current_debt), 0)         as total_debt,
                    COALESCE(SUM(total_purchases_year), 0) as total_purchases,
                    COALESCE(SUM(loyalty_points), 0)       as total_points
                ')
                ->first();

            $primary->update([
                'current_debt'         => (float) $primary->current_debt + (float) $agg->total_debt,
                'total_purchases_year' => (float) $primary->total_purchases_year + (float) $agg->total_purchases,
                'loyalty_points'       => (float) $primary->loyalty_points + (float) $agg->total_points,
            ]);

            // Re-pointer toutes les clés étrangères vers le primaire
            DB::table('sales')
                ->whereIn('reseller_id', $duplicateIds)
                ->update(['reseller_id' => $primaryId]);

            DB::table('reseller_payments')
                ->whereIn('reseller_id', $duplicateIds)
                ->update(['reseller_id' => $primaryId]);

            DB::table('product_returns')
                ->whereIn('reseller_id', $duplicateIds)
                ->update(['reseller_id' => $primaryId]);

            DB::table('pending_sales')
                ->whereIn('reseller_id', $duplicateIds)
                ->update(['reseller_id' => $primaryId]);

            // Pour les bonus de fidélité : fusionner les montants de même année
            $bonuses = DB::table('reseller_loyalty_bonuses')
                ->whereIn('reseller_id', $duplicateIds)
                ->get();

            foreach ($bonuses as $bonus) {
                $existing = DB::table('reseller_loyalty_bonuses')
                    ->where('reseller_id', $primaryId)
                    ->where('year', $bonus->year)
                    ->first();

                if ($existing) {
                    DB::table('reseller_loyalty_bonuses')
                        ->where('id', $existing->id)
                        ->update([
                            'total_purchases' => (float) $existing->total_purchases + (float) $bonus->total_purchases,
                            'bonus_amount'    => (float) $existing->bonus_amount    + (float) $bonus->bonus_amount,
                        ]);
                    DB::table('reseller_loyalty_bonuses')->where('id', $bonus->id)->delete();
                } else {
                    DB::table('reseller_loyalty_bonuses')
                        ->where('id', $bonus->id)
                        ->update(['reseller_id' => $primaryId]);
                }
            }

            // Soft-delete les doublons
            Reseller::whereIn('id', $duplicateIds)->delete();

            $primary->refresh();
            return $primary;
        });
    }

    /**
     * Retourne les groupes de revendeurs ayant le même nom de société
     * (suggestions de doublons probables).
     *
     * @return Collection<int, object{name:string, resellers:Collection}>
     */
    public function suggestedDuplicates(): Collection
    {
        return Reseller::active()
            ->get()
            ->groupBy(fn($r) => mb_strtolower(trim($r->company_name)))
            ->filter(fn($group) => $group->count() > 1)
            ->map(fn($group, $name) => (object) [
                'name'      => $group->first()->company_name,
                'resellers' => $group->values(),
            ])
            ->values();
    }
}

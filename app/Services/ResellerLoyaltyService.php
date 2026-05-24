<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerLoyaltyBonus;
use App\Models\Sale;
use Illuminate\Support\Collection;

final class ResellerLoyaltyService
{
    // ──────────────────────────────────────────────────────────────
    //  Tier de fidélité
    // ──────────────────────────────────────────────────────────────

    /**
     * Détermine le palier et le taux de bonus selon le montant payé.
     *
     * @return array{tier:string,rate:int}
     */
    public function resolveTier(float $totalPaid): array
    {
        return match (true) {
            $totalPaid >= 5_000_000 => ['tier' => 'diamond',  'rate' => 5],
            $totalPaid >= 2_500_000 => ['tier' => 'platinum', 'rate' => 4],
            $totalPaid >= 1_000_000 => ['tier' => 'gold',     'rate' => 3],
            $totalPaid >= 500_000   => ['tier' => 'silver',   'rate' => 2],
            default                 => ['tier' => 'bronze',   'rate' => 0],
        };
    }

    /**
     * Calcule le bonus de fidélité d'un revendeur pour une année.
     *
     * @return array{
     *   total_purchases:float,
     *   total_paid:float,
     *   tier:string,
     *   rate:int,
     *   bonus:float,
     *   is_paid:bool,
     * }
     */
    public function getLoyaltyData(Reseller $reseller, int $year): array
    {
        $start = "$year-01-01";
        $end   = "$year-12-31 23:59:59";

        $baseQuery = Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->where('payment_status', '!=', 'cancelled')
            ->whereBetween('created_at', [$start, $end]);

        $totalPurchases = (float) (clone $baseQuery)->sum('total_amount');
        $totalPaid      = (float) (clone $baseQuery)->sum('amount_paid');

        ['tier' => $tier, 'rate' => $rate] = $this->resolveTier($totalPaid);

        $bonus = round($totalPaid * $rate / 100);

        $isPaid = ResellerLoyaltyBonus::where('reseller_id', $reseller->id)
            ->where('year', $year)
            ->where('status', 'paid')
            ->exists();

        return [
            'total_purchases' => $totalPurchases,
            'total_paid'      => $totalPaid,
            'tier'            => $tier,
            'rate'            => $rate,
            'bonus'           => $bonus,
            'is_paid'         => $isPaid,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Relevé de compte
    // ──────────────────────────────────────────────────────────────

    /**
     * Construit les données complètes du relevé de compte.
     *
     * @return array{
     *   openingBalance:float,
     *   movements:Collection,
     *   sales:Collection,
     *   payments:Collection,
     *   summary:array<string,float>,
     * }
     */
    public function buildStatement(Reseller $reseller, string $start, string $end, ?int $shopId = null): array
    {
        $openingBalance = $this->computeOpeningBalance($reseller, $start, $shopId);

        $sales = Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->with(['items.product', 'shop'])
            ->orderBy('created_at')
            ->get();

        $payments = $reseller->payments()
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        $movements = $this->buildMovements($sales);

        $totalPurchases = (float) $sales->sum('total_amount');
        // amount_paid est mis à jour par distributePaymentToSales() lors de chaque paiement :
        // il représente déjà la somme de l'acompte initial + tous les ResellerPayments appliqués.
        // Ne pas ajouter payments->sum('amount') en plus, ce serait un double-comptage.
        $totalPayments  = (float) $sales->sum('amount_paid');

        $summary = [
            'total_purchases' => $totalPurchases,
            'total_payments'  => $totalPayments,
            'total_discount'  => (float) $sales->sum(fn($s) => $s->discount_amount ?? 0),
            'balance'         => max(0.0, $totalPurchases - $totalPayments),
        ];

        return compact('openingBalance', 'movements', 'sales', 'payments', 'summary');
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers internes
    // ──────────────────────────────────────────────────────────────

    private function computeOpeningBalance(Reseller $reseller, string $start, ?int $shopId = null): float
    {
        $base = Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->where('created_at', '<', $start);

        $salesBefore = (float) (clone $base)->sum('total_amount');
        // amount_paid reflète tous les paiements reçus (acompte initial + ResellerPayments appliqués).
        // Soustraire paymentsBefore en plus serait un double-comptage.
        $paidBefore  = (float) (clone $base)->sum('amount_paid');

        return max(0.0, $salesBefore - $paidBefore);
    }

    private function buildMovements(Collection $sales): Collection
    {
        $movements = collect();

        foreach ($sales as $sale) {
            $products  = $sale->items->map(fn($item) => [
                'name'       => $item->product->name ?? 'Produit supprimé',
                'quantity'   => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount'   => (float) ($item->discount ?? 0),
                'total'      => (float) $item->total_price,
            ])->toArray();

            $shopLabel = $sale->shop?->name ?? '—';

            $movements->push([
                'date'        => $sale->created_at,
                'type'        => 'sale',
                'reference'   => $sale->invoice_number ?? ('VTE-' . $sale->id),
                'sale_id'     => $sale->id,
                'description' => count($sale->items) . ' article(s)',
                'products'    => $products,
                'shop'        => $shopLabel,
                'debit'       => (float) $sale->total_amount,
                'credit'      => 0.0,
            ]);

            if ($sale->amount_paid > 0) {
                $movements->push([
                    'date'        => $sale->created_at,
                    'type'        => 'payment',
                    'reference'   => 'ACP-' . $sale->id,
                    'description' => 'Acompte sur vente VTE-' . $sale->id,
                    'shop'        => $shopLabel,
                    'debit'       => 0.0,
                    'credit'      => (float) $sale->amount_paid,
                ]);
            }
        }

        // ResellerPayments are intentionally NOT added to movements.
        // distributePaymentToSales() already updates sale->amount_paid, so ACPs above
        // capture all credits (initial deposit + subsequent distributions).
        // Adding PAY entries would double-count every distributed payment.
        // PAYs remain visible in the separate versements table.

        return $movements->sortBy('date')->values();
    }
}

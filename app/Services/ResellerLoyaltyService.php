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

        $totalPurchases = (float) Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $totalPaid = (float) Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount_paid');

        ['tier' => $tier, 'rate' => $rate] = $this->resolveTier($totalPaid);

        $bonus = round($totalPaid * $rate / 100);

        $isPaid = ResellerLoyaltyBonus::where('reseller_id', $reseller->id)
            ->where('year', $year)
            ->where('status', 'paid')
            ->exists();

        return compact('totalPurchases', 'totalPaid', 'tier', 'rate', 'bonus', 'isPaid') + [
            'total_purchases' => $totalPurchases,
            'total_paid'      => $totalPaid,
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

        $movements = $this->buildMovements($sales, $payments);

        $totalPurchases = (float) $sales->sum('total_amount');
        $totalPayments  = (float) $payments->sum('amount') + (float) $sales->sum('amount_paid');

        $summary = [
            'total_purchases' => $totalPurchases,
            'total_payments'  => $totalPayments,
            'total_discount'  => (float) $sales->sum(fn($s) => $s->discount_amount ?? 0),
            'balance'         => $totalPurchases - $totalPayments,
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

        $salesBefore    = (float) (clone $base)->sum('total_amount');
        $paidBefore     = (float) (clone $base)->sum('amount_paid');
        $paymentsBefore = (float) $reseller->payments()->where('created_at', '<', $start)->sum('amount');

        return $salesBefore - $paymentsBefore - $paidBefore;
    }

    private function buildMovements(Collection $sales, Collection $payments): Collection
    {
        $movements = collect();

        foreach ($sales as $sale) {
            $products  = $sale->items->map(fn($item) => [
                'name'     => $item->product->name ?? 'Produit supprimé',
                'quantity' => $item->quantity,
                'total'    => $item->total_price,
            ])->toArray();

            $shopLabel = $sale->shop?->name ?? '—';

            $movements->push([
                'date'        => $sale->created_at,
                'type'        => 'sale',
                'reference'   => $sale->reference ?? 'VTE-' . $sale->id,
                'sale_id'     => $sale->id,
                'description' => 'Vente - ' . count($sale->items) . ' article(s)',
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

        foreach ($payments as $payment) {
            $movements->push([
                'date'        => $payment->created_at,
                'type'        => 'payment',
                'reference'   => $payment->reference ?? 'PAY-' . $payment->id,
                'description' => 'Paiement - ' . ucfirst($payment->payment_method ?? 'Espèces'),
                'shop'        => '—',
                'debit'       => 0.0,
                'credit'      => (float) $payment->amount,
            ]);
        }

        return $movements->sortBy('date')->values();
    }
}

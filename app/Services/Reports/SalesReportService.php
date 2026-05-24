<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesReportService
{
    // ──────────────────────────────────────────────────────────────
    //  Query builder partagé
    // ──────────────────────────────────────────────────────────────

    private function baseQuery(
        string $start,
        string $end,
        ?int $shopId,
        ?int $customerId  = null,
        ?int $categoryId  = null,
        ?int $productId   = null,
        ?int $resellerId  = null,
    ): Builder {
        $q = Sale::withoutGlobalScope('shop')
            ->where('payment_status', '!=', 'cancelled')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);

        if ($shopId)    $q->where('shop_id', $shopId);
        if ($customerId) $q->where('customer_id', $customerId);
        if ($resellerId) $q->where('reseller_id', $resellerId);
        if ($categoryId) $q->whereHas('items.product', fn($sq) => $sq->where('category_id', $categoryId));
        if ($productId)  $q->whereHas('items', fn($sq) => $sq->where('product_id', $productId));

        return $q;
    }

    // ──────────────────────────────────────────────────────────────
    //  KPIs
    // ──────────────────────────────────────────────────────────────

    /** @return array{totalSales:int,totalRevenue:float,totalPaid:float,totalCredit:float,totalDiscount:float} */
    public function getKpis(
        string $start,
        string $end,
        ?int $shopId,
        ?int $customerId = null,
        ?int $categoryId = null,
        ?int $productId  = null,
        ?int $resellerId = null,
    ): array {
        $q = $this->baseQuery($start, $end, $shopId, $customerId, $categoryId, $productId, $resellerId);

        $totalRevenue = (clone $q)->sum('total_amount');
        $totalPaid    = (clone $q)->sum('amount_paid');

        // Ajouter les pièces réparations au CA (workflow caissier)
        $repairParts = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ->sum('parts_cost');
        $totalRevenue += $repairParts;
        $totalPaid    += $repairParts;

        $globalDiscount = (float) (clone $q)->sum('discount_amount');
        $itemDiscount   = (float) SaleItem::whereIn('sale_id', (clone $q)->select('id'))->sum('discount');

        return [
            'totalSales'    => (clone $q)->count(),
            'totalRevenue'  => (float) $totalRevenue,
            'totalPaid'     => (float) $totalPaid,
            'totalCredit'   => (float) (clone $q)->where('payment_status', 'credit')->sum('total_amount'),
            'totalDiscount' => $globalDiscount + $itemDiscount,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Évolution par jour (+ pièces réparation)
    // ──────────────────────────────────────────────────────────────

    public function getByDay(
        string $start,
        string $end,
        ?int $shopId,
        ?int $customerId = null,
        ?int $categoryId = null,
        ?int $productId  = null,
        ?int $resellerId = null,
    ): Collection {
        $q = $this->baseQuery($start, $end, $shopId, $customerId, $categoryId, $productId, $resellerId);

        $salesByDay = (clone $q)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ajouter pièces réparation (par jour)
        $repairPartsBase = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('parts_cost', '>', 0);
        if ($shopId) $repairPartsBase->where('shop_id', $shopId);

        $repairsByDay = (clone $repairPartsBase)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(parts_cost) as total'))
            ->groupBy('date')->orderBy('date')->get();

        foreach ($repairsByDay as $rbd) {
            $found = $salesByDay->first(fn($d) => $d->date === $rbd->date);
            if ($found) {
                $found->total += $rbd->total;
                $found->count += $rbd->count;
            } else {
                $salesByDay->push($rbd);
            }
        }

        return $salesByDay->sortBy('date')->values();
    }

    // ──────────────────────────────────────────────────────────────
    //  Répartitions
    // ──────────────────────────────────────────────────────────────

    public function getByPayment(
        string $start,
        string $end,
        ?int $shopId,
        ?int $customerId = null,
        ?int $categoryId = null,
        ?int $productId  = null,
        ?int $resellerId = null,
    ): Collection {
        $q = $this->baseQuery($start, $end, $shopId, $customerId, $categoryId, $productId, $resellerId);

        $byPayment = (clone $q)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Ajouter pièces réparation aux modes de paiement
        $repairsBase = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('parts_cost', '>', 0);
        if ($shopId) $repairsBase->where('shop_id', $shopId);

        $repairsByPayment = (clone $repairsBase)
            ->select('payment_method', DB::raw('SUM(parts_cost) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')->get();

        foreach ($repairsByPayment as $rbp) {
            $found = $byPayment->first(fn($p) => $p->payment_method === $rbp->payment_method);
            if ($found) {
                $found->total += $rbp->total;
                $found->count += $rbp->count;
            } else {
                $byPayment->push((object) ['payment_method' => $rbp->payment_method, 'count' => $rbp->count, 'total' => $rbp->total]);
            }
        }

        return $byPayment;
    }

    public function getByClientType(
        string $start,
        string $end,
        ?int $shopId,
        ?int $customerId = null,
        ?int $categoryId = null,
        ?int $productId  = null,
        ?int $resellerId = null,
    ): Collection {
        $q = $this->baseQuery($start, $end, $shopId, $customerId, $categoryId, $productId, $resellerId);

        $byType = (clone $q)
            ->select('client_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('client_type')
            ->get();

        // Les pièces réparation appartiennent toujours au type 'customer'
        $repairsBase = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('parts_cost', '>', 0);
        if ($shopId) $repairsBase->where('shop_id', $shopId);

        $partsTotal = (clone $repairsBase)->sum('parts_cost');
        $partsCount = (clone $repairsBase)->count();

        $customerRow = $byType->first(fn($r) => $r->client_type === 'customer');
        if ($customerRow) {
            $customerRow->total += $partsTotal;
            $customerRow->count += $partsCount;
        } elseif ($partsTotal > 0) {
            $byType->push((object) ['client_type' => 'customer', 'count' => $partsCount, 'total' => $partsTotal]);
        }

        return $byType;
    }

    public function getByUser(string $start, string $end, ?int $shopId): Collection
    {
        $q = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $byUser = $q->with('user')
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('user_id')
            ->get();

        // Ajouter pièces réparation par créateur
        $repairsBase = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('parts_cost', '>', 0);
        if ($shopId) $repairsBase->where('shop_id', $shopId);

        $repairsByCreator = (clone $repairsBase)->with('creator')
            ->select('created_by', DB::raw('SUM(parts_cost) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('created_by')->get();

        foreach ($repairsByCreator as $rbc) {
            $found = $byUser->first(fn($u) => $u->user_id === $rbc->created_by);
            if ($found) {
                $found->total += $rbc->total;
                $found->count += $rbc->count;
            } else {
                $byUser->push((object) ['user_id' => $rbc->created_by, 'user' => $rbc->creator, 'count' => $rbc->count, 'total' => $rbc->total]);
            }
        }

        return $byUser;
    }

    // ──────────────────────────────────────────────────────────────
    //  Top produits / clients / revendeurs
    // ──────────────────────────────────────────────────────────────

    public function getTopProducts(
        string $start,
        string $end,
        ?int $shopId,
        ?int $customerId = null,
        ?int $categoryId = null,
        ?int $productId  = null,
        int $limit = 10,
        ?int $resellerId = null,
    ): Collection {
        return SaleItem::whereHas('sale', function ($q) use ($start, $end, $shopId, $customerId, $resellerId) {
            $q->withoutGlobalScope('shop')->whereBetween('created_at', [$start, $end . ' 23:59:59']);
            if ($shopId)     $q->where('shop_id', $shopId);
            if ($customerId) $q->where('customer_id', $customerId);
            if ($resellerId) $q->where('reseller_id', $resellerId);
        })
            ->when($categoryId, fn($q) => $q->whereHas('product', fn($sq) => $sq->where('category_id', $categoryId)))
            ->when($productId,  fn($q) => $q->where('product_id', $productId))
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    public function getTopCustomers(string $start, string $end, ?int $shopId, int $limit = 10): Collection
    {
        $q = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->whereNotNull('customer_id')
            ->with(['customer' => fn($cq) => $cq->withoutGlobalScope('shop')])
            ->select('customer_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function getTopResellers(string $start, string $end, ?int $shopId, int $limit = 10): Collection
    {
        $q = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->whereNotNull('reseller_id')
            ->with('reseller')
            ->select('reseller_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('reseller_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Comparaisons temporelles
    // ──────────────────────────────────────────────────────────────

    /** @return array{previousRevenue:float,previousSales:int,revenueGrowth:float} */
    public function getPreviousPeriod(string $start, string $end, ?int $shopId, float $totalRevenue): array
    {
        $daysDiff      = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
        $previousStart = Carbon::parse($start)->subDays($daysDiff)->format('Y-m-d');
        $previousEnd   = Carbon::parse($start)->subDay()->format('Y-m-d');

        $q = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$previousStart, $previousEnd . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $previousRevenue = (float) (clone $q)->sum('total_amount');
        $previousSales   = (clone $q)->count();
        $revenueGrowth   = $previousRevenue > 0
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        return compact('previousRevenue', 'previousSales', 'revenueGrowth');
    }

    /** @return array{n1Revenue:float,n1Sales:int,n1Growth:float|null} */
    public function getN1(string $start, string $end, ?int $shopId, float $totalRevenue): array
    {
        $n1Start = Carbon::parse($start)->subYear()->format('Y-m-d');
        $n1End   = Carbon::parse($end)->subYear()->format('Y-m-d');

        $q = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$n1Start, $n1End . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $n1Revenue = (float) (clone $q)->sum('total_amount');
        $n1Sales   = (clone $q)->count();
        $n1Growth  = $n1Revenue > 0
            ? round((($totalRevenue - $n1Revenue) / $n1Revenue) * 100, 1)
            : null;

        return compact('n1Revenue', 'n1Sales', 'n1Growth');
    }
}

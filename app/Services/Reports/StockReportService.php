<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockReportService
{
    // ──────────────────────────────────────────────────────────────
    //  Query builder partagé
    // ──────────────────────────────────────────────────────────────

    private function baseQuery(?int $shopId, ?int $categoryId): \Illuminate\Database\Eloquent\Builder
    {
        $q = Product::withoutGlobalScope('shop');
        if ($shopId)     $q->where('shop_id', $shopId);
        if ($categoryId) $q->where('category_id', $categoryId);
        return $q;
    }

    // ──────────────────────────────────────────────────────────────
    //  KPIs
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   totalStockValue:float,
     *   totalSellingValue:float,
     *   potentialProfit:float,
     *   totalProducts:int,
     *   activeProducts:int,
     *   outOfStock:int,
     *   lowStock:int,
     * }
     */
    public function getKpis(?int $shopId, ?int $categoryId): array
    {
        $q = $this->baseQuery($shopId, $categoryId);

        $totalStockValue   = (float) (clone $q)->sum(DB::raw('quantity_in_stock * purchase_price'));
        $totalSellingValue = (float) (clone $q)->sum(DB::raw('quantity_in_stock * normal_price'));
        $potentialProfit   = $totalSellingValue - $totalStockValue;

        $totalProducts  = (clone $q)->count();
        $activeProducts = (clone $q)->where('is_active', true)->count();
        $outOfStock     = (clone $q)->where('quantity_in_stock', 0)->count();
        $lowStock       = (clone $q)->whereRaw('quantity_in_stock <= stock_alert_threshold')->where('quantity_in_stock', '>', 0)->count();

        return compact(
            'totalStockValue', 'totalSellingValue', 'potentialProfit',
            'totalProducts', 'activeProducts', 'outOfStock', 'lowStock',
        );
    }

    // ──────────────────────────────────────────────────────────────
    //  Répartitions
    // ──────────────────────────────────────────────────────────────

    public function getByCategory(?int $shopId, ?int $categoryId): Collection
    {
        return $this->baseQuery($shopId, $categoryId)
            ->with('category')
            ->select(
                'category_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(quantity_in_stock) as total_qty'),
                DB::raw('SUM(quantity_in_stock * purchase_price) as total_value'),
            )
            ->groupBy('category_id')
            ->get();
    }

    public function getProductsToOrder(?int $shopId, ?int $categoryId): Collection
    {
        return $this->baseQuery($shopId, $categoryId)
            ->with('category')
            ->whereRaw('quantity_in_stock <= stock_alert_threshold')
            ->orderBy('quantity_in_stock')
            ->get();
    }

    public function getMostProfitable(?int $shopId, ?int $categoryId, int $limit = 10): Collection
    {
        return $this->baseQuery($shopId, $categoryId)
            ->where('quantity_in_stock', '>', 0)
            ->selectRaw('*, (normal_price - purchase_price) as profit_margin, ((normal_price - purchase_price) / purchase_price * 100) as profit_percentage')
            ->orderByDesc('profit_margin')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Rotation & produits dormants
    // ──────────────────────────────────────────────────────────────

    public function getStockRotation(?int $shopId, ?int $categoryId, int $days = 30, int $limit = 20): Collection
    {
        return SaleItem::where('created_at', '>=', Carbon::now()->subDays($days))
            ->with('product')
            ->when($categoryId, fn($q) => $q->whereHas('product', fn($sq) => $sq->where('category_id', $categoryId)))
            ->select('product_id', DB::raw('SUM(quantity) as sold_qty'))
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit($limit)
            ->get();
    }

    public function getDormantProducts(?int $shopId, ?int $categoryId, int $days = 30): Collection
    {
        return $this->baseQuery($shopId, $categoryId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->whereDoesntHave('saleItems', function ($q) use ($days) {
                $q->where('created_at', '>=', Carbon::now()->subDays($days));
            })
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Mouvements récents
    // ──────────────────────────────────────────────────────────────

    public function getRecentMovements(?int $shopId, int $limit = 20): Collection
    {
        $q = StockMovement::withoutGlobalScope('shop')->with(['product', 'user']);
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->latest()->limit($limit)->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Données pour le PDF (version allégée sans rotation/dormants)
    // ──────────────────────────────────────────────────────────────

    public function getMostProfitablePdf(?int $shopId, ?int $categoryId, int $limit = 10): Collection
    {
        return $this->baseQuery($shopId, $categoryId)
            ->where('quantity_in_stock', '>', 0)
            ->selectRaw('*, (normal_price - purchase_price) as profit_margin')
            ->orderByDesc('profit_margin')
            ->limit($limit)
            ->get();
    }
}

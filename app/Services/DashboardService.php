<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SavReplacedPart;
use App\Models\SavTicket;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use App\Services\Reports\FinancialReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function __construct(
        private readonly FinancialReportService $financialService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    //  Point d'entrée principal
    // ──────────────────────────────────────────────────────────────

    /**
     * Retourne tous les KPIs du tableau de bord admin, déjà combinés.
     *
     * @return array<string,mixed>
     */
    public function getStats(?int $shopId): array
    {
        $stats = array_merge(
            $this->getUserStats($shopId),
            $this->getSalesStats($shopId),
            $this->getExpenseStats($shopId),
            $this->getRepairStats($shopId),
            $this->getSavStats($shopId),
            $this->getResellerStats($shopId),
        );

        // Bénéfice net du jour (calcul rapide sur les stats déjà chargées)
        $stats['today_profit'] = $stats['today_sales_amount']
            + $stats['_today_repair_admin_share']
            - ($stats['_today_purchase_cost'] + $stats['today_expenses'] + $stats['_today_sav_cost']);

        // Bénéfice net du mois = même calcul que /reports/financial pour cohérence
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $today        = now()->format('Y-m-d');

        [
            'savRefunds'        => $savRefunds,
            'savExchangeLosses' => $savExchangeLosses,
            'savExchangeGains'  => $savExchangeGains,
            'savTotalImpact'    => $savTotalImpact,
        ] = $this->financialService->getSavImpact($startOfMonth, $today, $shopId);

        [
            'salesRevenue'   => $salesRevenue,
            'repairsRevenue' => $repairsRevenue,
        ] = $this->financialService->getRevenue($startOfMonth, $today, $shopId, $savExchangeGains, $savRefunds, $savExchangeLosses);

        ['totalExpenses' => $totalExpenses] =
            $this->financialService->getExpenses($startOfMonth, $today, $shopId);

        ['finalNetProfit' => $stats['month_profit']] =
            $this->financialService->getMargin(
                $startOfMonth, $today, $shopId,
                $salesRevenue, $repairsRevenue, $savTotalImpact, $totalExpenses
            );

        // Pièces réparation → ajoutées au CA ventes (après calcul du bénéfice)
        $stats['today_sales_amount'] += $stats['_today_repair_parts'];
        $stats['month_sales_amount'] += $stats['month_repair_parts'];

        foreach ([
            '_today_purchase_cost', '_month_purchase_cost',
            '_today_sav_cost', '_month_sav_cost',
            '_today_repair_admin_share', '_month_repair_admin_share_calc',
            '_today_repair_parts',
        ] as $key) {
            unset($stats[$key]);
        }

        return $stats;
    }

    // ──────────────────────────────────────────────────────────────
    //  KPIs par domaine
    // ──────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    public function getUserStats(?int $shopId): array
    {
        return [
            'total_users'           => User::when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'active_users'          => User::active()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'total_customers'       => Customer::when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'total_resellers'       => Reseller::when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))->count(),
            'total_products'        => Product::when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'low_stock_products'    => Product::lowStock()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'out_of_stock_products' => Product::outOfStock()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
        ];
    }

    /** @return array<string,mixed> */
    public function getSalesStats(?int $shopId): array
    {
        $todayStats = Sale::today()->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();

        $monthStats = Sale::thisMonth()->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();

        $todayPurchaseCost = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereDate('sales.created_at', today())
            ->whereNull('sales.deleted_at')
            ->when($shopId, fn($q) => $q->where('sales.shop_id', $shopId))
            ->sum(DB::raw('sale_items.quantity * products.purchase_price'));

        $monthPurchaseCost = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereMonth('sales.created_at', now()->month)
            ->whereYear('sales.created_at', now()->year)
            ->whereNull('sales.deleted_at')
            ->when($shopId, fn($q) => $q->where('sales.shop_id', $shopId))
            ->sum(DB::raw('sale_items.quantity * products.purchase_price'));

        return [
            'today_sales_count'    => (int) ($todayStats->count ?? 0),
            'today_sales_amount'   => (float) ($todayStats->total ?? 0),
            'month_sales_count'    => (int) ($monthStats->count ?? 0),
            'month_sales_amount'   => (float) ($monthStats->total ?? 0),
            '_today_purchase_cost' => (float) $todayPurchaseCost,
            '_month_purchase_cost' => (float) $monthPurchaseCost,
        ];
    }

    /** @return array<string,mixed> */
    public function getExpenseStats(?int $shopId): array
    {
        return [
            'today_expenses'   => (float) Expense::today()->approved()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->sum('amount'),
            'month_expenses'   => (float) Expense::currentMonth()->approved()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->sum('amount'),
            'pending_expenses' => Expense::pending()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
        ];
    }

    /** @return array<string,mixed> */
    public function getRepairStats(?int $shopId): array
    {
        $techSharePercent  = (int) Setting::get('technician_labor_share', 50);
        $adminSharePercent = 100 - $techSharePercent;

        $monthDeliveredRepairs = Repair::where('status', Repair::STATUS_DELIVERED)
            ->whereMonth('repaired_at', now()->month)
            ->whereYear('repaired_at', now()->year)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        $monthRepairLaborTotal = (float) $monthDeliveredRepairs->sum('labor_cost');
        $monthRepairPartsTotal = (float) $monthDeliveredRepairs->sum('parts_cost');

        $todayDeliveredRepairs = Repair::where('status', Repair::STATUS_DELIVERED)
            ->whereDate('delivered_at', today())
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        $todayRepairLaborTotal = (float) $todayDeliveredRepairs->sum('labor_cost');
        $todayRepairPartsTotal = (float) $todayDeliveredRepairs->sum('parts_cost');

        $monthSavRepairDeductions = (float) SavReplacedPart::where('ca_deducted', true)
            ->whereMonth('deducted_at', now()->month)
            ->whereYear('deducted_at', now()->year)
            ->sum('defective_part_cost');

        return [
            'pending_repairs'             => Repair::pending()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'today_repairs'               => Repair::today()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'stale_repairs'               => Repair::pending()
                ->where('updated_at', '<', now()->subDays(7))
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->count(),
            'tech_share_percent'          => $techSharePercent,
            'month_repair_labor'          => $monthRepairLaborTotal,
            'month_repair_parts'          => $monthRepairPartsTotal,
            'month_repair_admin_share'    => $monthRepairLaborTotal * $adminSharePercent / 100,
            'month_repair_tech_share'     => $monthRepairLaborTotal * $techSharePercent / 100,
            'month_sav_repair_deductions' => $monthSavRepairDeductions,
            '_today_repair_admin_share'      => $todayRepairLaborTotal * $adminSharePercent / 100,
            '_month_repair_admin_share_calc' => $monthRepairLaborTotal * $adminSharePercent / 100,
            '_today_repair_parts'            => $todayRepairPartsTotal,
        ];
    }

    /** @return array<string,mixed> */
    public function getSavStats(?int $shopId): array
    {
        $todaySavCost = (float) SavTicket::whereDate('created_at', today())
            ->where('type', '!=', 'repair_warranty')
            ->whereIn('status', ['resolved', 'closed'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('refund_amount');

        $monthSavCost = (float) SavTicket::where('created_at', '>=', now()->startOfMonth())
            ->where('type', '!=', 'repair_warranty')
            ->whereIn('status', ['resolved', 'closed'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('refund_amount');

        return [
            'sav_open_tickets'  => SavTicket::open()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'sav_urgent_tickets'=> SavTicket::open()->urgent()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'sav_month_refunds' => (float) SavTicket::where('created_at', '>=', now()->startOfMonth())
                ->whereIn('status', ['resolved', 'closed'])
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->sum('refund_amount'),
            'sav_month_tickets' => SavTicket::where('created_at', '>=', now()->startOfMonth())
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            '_today_sav_cost'   => $todaySavCost,
            '_month_sav_cost'   => $monthSavCost,
        ];
    }

    /** @return array<string,mixed> */
    public function getResellerStats(?int $shopId): array
    {
        return [
            'total_debt'          => (float) Reseller::when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))->sum('current_debt'),
            'resellers_with_debt' => Reseller::withDebt()->when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Données récentes et valeur stock
    // ──────────────────────────────────────────────────────────────

    /** @return array<int,mixed> */
    public function getRecentData(?int $shopId): array
    {
        $recentSales = Sale::with(['user', 'customer', 'reseller', 'shop'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->latest()->take(10)->get();

        $recentRepairs = Repair::with(['customer', 'technician', 'shop'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->latest()->take(10)->get();

        $lowStockProducts = Product::lowStock()
            ->with(['category', 'shop'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->take(10)->get();

        $urgentSavTickets = SavTicket::open()->urgent()
            ->with(['customer', 'creator', 'shop'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->latest()->take(5)->get();

        $topExpenseCategories = Expense::currentMonth()->approved()
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->with('category')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->take(5)->get();

        $recentExpenses = Expense::with(['category', 'user', 'shop'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->latest()->take(5)->get();

        $deliveredRepairs = Repair::whereIn('status', [
                Repair::STATUS_DELIVERED,
                Repair::STATUS_REPAIRED,
                Repair::STATUS_READY_FOR_PICKUP,
            ])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->with(['customer', 'technician'])
            ->latest('repaired_at')->take(10)->get();

        return [
            $recentSales, $recentRepairs, $lowStockProducts, $urgentSavTickets,
            $topExpenseCategories, $recentExpenses, $deliveredRepairs,
        ];
    }

    /** @return array{0:Collection,1:float} */
    public function getStockValue(?int $shopId): array
    {
        $shops = $shopId
            ? Shop::active()->where('id', $shopId)->get()
            : Shop::active()->get();

        $stockValueByShop = $shops->map(function ($shop) {
            $stockValue = Product::withoutGlobalScope('shop')
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->selectRaw('SUM(quantity_in_stock * purchase_price) as total_value')
                ->value('total_value') ?? 0;

            $productCount = Product::withoutGlobalScope('shop')
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->sum('quantity_in_stock');

            return ['shop' => $shop, 'stock_value' => (float) $stockValue, 'product_count' => (int) $productCount];
        });

        return [$stockValueByShop, (float) $stockValueByShop->sum('stock_value')];
    }
}

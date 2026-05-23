<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\SaleItem;
use App\Models\SavReplacedPart;
use App\Models\Setting;
use App\Models\Sale;
use App\Models\SavTicket;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord Admin - Vue d'ensemble en lecture seule
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::active()->orderBy('name')->get();
        $selectedShopId = $request->input('shop_id');
        $selectedShop = $selectedShopId ? $shops->firstWhere('id', $selectedShopId) : null;

        $shopFilter = function ($query) use ($selectedShopId) {
            if ($selectedShopId) {
                $query->where('shop_id', $selectedShopId);
            }
        };

        $stats = array_merge(
            $this->getUserStats($selectedShopId, $shopFilter),
            $this->getSalesStats($selectedShopId, $shopFilter),
            $this->getExpenseStats($selectedShopId, $shopFilter),
            $this->getRepairStats($selectedShopId, $shopFilter),
            $this->getSavStats($selectedShopId, $shopFilter),
            $this->getResellerStats($selectedShopId, $shopFilter)
        );

        // Bénéfice net = CA + Part boutique réparations - (Coût achat + Dépenses + SAV produit)
        $stats['today_profit'] = $stats['today_sales_amount']
            + $stats['_today_repair_admin_share']
            - ($stats['_today_purchase_cost'] + $stats['today_expenses'] + $stats['_today_sav_cost']);
        $stats['month_profit'] = $stats['month_sales_amount']
            + $stats['_month_repair_admin_share_calc']
            - ($stats['_month_purchase_cost'] + $stats['month_expenses'] + $stats['_month_sav_cost']);

        // Pièces de réparation → ventes (ajout après le bénéfice pour ne pas biaiser le calcul)
        $stats['today_sales_amount'] += $stats['_today_repair_parts'];
        $stats['month_sales_amount'] += $stats['month_repair_parts'];

        // Supprimer les clés de calcul intermédiaire
        foreach ([
            '_today_purchase_cost', '_month_purchase_cost',
            '_today_sav_cost', '_month_sav_cost',
            '_today_repair_admin_share', '_month_repair_admin_share_calc',
            '_today_repair_parts',
        ] as $key) {
            unset($stats[$key]);
        }

        [$recentSales, $recentRepairs, $lowStockProducts, $urgentSavTickets,
         $topExpenseCategories, $recentExpenses, $deliveredRepairs] = $this->getRecentData($selectedShopId, $shopFilter);

        [$stockValueByShop, $totalStockValue] = $this->getStockValue($selectedShopId);

        return view('admin.dashboard', compact(
            'stats',
            'shops',
            'selectedShopId',
            'selectedShop',
            'recentSales',
            'recentRepairs',
            'lowStockProducts',
            'urgentSavTickets',
            'topExpenseCategories',
            'recentExpenses',
            'stockValueByShop',
            'totalStockValue',
            'deliveredRepairs'
        ));
    }

    private function getUserStats(?int $shopId, callable $shopFilter): array
    {
        return [
            'total_users'        => User::when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'active_users'       => User::active()->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count(),
            'total_customers'    => Customer::when($shopId, $shopFilter)->count(),
            'total_resellers'    => Reseller::when($shopId, $shopFilter)->count(),
            'total_products'     => Product::when($shopId, $shopFilter)->count(),
            'low_stock_products' => Product::lowStock()->when($shopId, $shopFilter)->count(),
            'out_of_stock_products' => Product::outOfStock()->when($shopId, $shopFilter)->count(),
        ];
    }

    private function getSalesStats(?int $shopId, callable $shopFilter): array
    {
        $todayStats = Sale::today()->when($shopId, $shopFilter)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();

        $monthStats = Sale::thisMonth()->when($shopId, $shopFilter)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();

        // Coût d'achat via JOIN SQL — pas de boucle PHP
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
            '_today_purchase_cost' => $todayPurchaseCost,
            '_month_purchase_cost' => $monthPurchaseCost,
        ];
    }

    private function getExpenseStats(?int $shopId, callable $shopFilter): array
    {
        return [
            'today_expenses'   => Expense::today()->approved()->when($shopId, $shopFilter)->sum('amount'),
            'month_expenses'   => Expense::currentMonth()->approved()->when($shopId, $shopFilter)->sum('amount'),
            'pending_expenses' => Expense::pending()->when($shopId, $shopFilter)->count(),
        ];
    }

    private function getRepairStats(?int $shopId, callable $shopFilter): array
    {
        $techSharePercent  = (int) Setting::get('technician_labor_share', 50);
        $adminSharePercent = 100 - $techSharePercent;

        $monthDeliveredRepairs = Repair::where('status', Repair::STATUS_DELIVERED)
            ->whereMonth('repaired_at', now()->month)
            ->whereYear('repaired_at', now()->year)
            ->when($shopId, $shopFilter)
            ->get();

        $monthRepairLaborTotal = $monthDeliveredRepairs->sum('labor_cost');
        $monthRepairPartsTotal = $monthDeliveredRepairs->sum('parts_cost');

        // Réparations livrées aujourd'hui
        $todayDeliveredRepairs = Repair::where('status', Repair::STATUS_DELIVERED)
            ->whereDate('delivered_at', today())
            ->when($shopId, $shopFilter)
            ->get();
        $todayRepairLaborTotal = $todayDeliveredRepairs->sum('labor_cost');
        $todayRepairPartsTotal = $todayDeliveredRepairs->sum('parts_cost');

        $monthSavRepairDeductions = SavReplacedPart::where('ca_deducted', true)
            ->whereMonth('deducted_at', now()->month)
            ->whereYear('deducted_at', now()->year)
            ->sum('defective_part_cost');

        return [
            'pending_repairs'              => Repair::pending()->when($shopId, $shopFilter)->count(),
            'today_repairs'                => Repair::today()->when($shopId, $shopFilter)->count(),
            // Réparations sans activité depuis 7+ jours (hors statuts terminaux)
            'stale_repairs'                => Repair::pending()
                ->where('updated_at', '<', now()->subDays(7))
                ->when($shopId, $shopFilter)
                ->count(),
            'tech_share_percent'           => $techSharePercent,
            'month_repair_labor'           => $monthRepairLaborTotal,
            'month_repair_parts'           => $monthRepairPartsTotal,
            'month_repair_admin_share'     => $monthRepairLaborTotal * $adminSharePercent / 100,
            'month_repair_tech_share'      => $monthRepairLaborTotal * $techSharePercent / 100,
            'month_sav_repair_deductions'  => $monthSavRepairDeductions,
            // Clés intermédiaires pour le calcul du bénéfice et l'affichage
            '_today_repair_admin_share'      => $todayRepairLaborTotal * $adminSharePercent / 100,
            '_month_repair_admin_share_calc' => $monthRepairLaborTotal * $adminSharePercent / 100,
            '_today_repair_parts'            => $todayRepairPartsTotal,
        ];
    }

    private function getSavStats(?int $shopId, callable $shopFilter): array
    {
        $todaySavCost = SavTicket::whereDate('created_at', today())
            ->where('type', '!=', 'repair_warranty')
            ->whereIn('status', ['resolved', 'closed'])
            ->when($shopId, $shopFilter)
            ->sum('refund_amount');

        $monthSavCost = SavTicket::where('created_at', '>=', now()->startOfMonth())
            ->where('type', '!=', 'repair_warranty')
            ->whereIn('status', ['resolved', 'closed'])
            ->when($shopId, $shopFilter)
            ->sum('refund_amount');

        return [
            'sav_open_tickets'    => SavTicket::open()->when($shopId, $shopFilter)->count(),
            'sav_urgent_tickets'  => SavTicket::open()->urgent()->when($shopId, $shopFilter)->count(),
            'sav_month_refunds'   => SavTicket::where('created_at', '>=', now()->startOfMonth())
                ->whereIn('status', ['resolved', 'closed'])
                ->when($shopId, $shopFilter)
                ->sum('refund_amount'),
            'sav_month_tickets'   => SavTicket::where('created_at', '>=', now()->startOfMonth())
                ->when($shopId, $shopFilter)->count(),
            '_today_sav_cost'     => $todaySavCost,
            '_month_sav_cost'     => $monthSavCost,
        ];
    }

    private function getResellerStats(?int $shopId, callable $shopFilter): array
    {
        return [
            'total_debt'          => Reseller::when($shopId, $shopFilter)->sum('current_debt'),
            'resellers_with_debt' => Reseller::withDebt()->when($shopId, $shopFilter)->count(),
        ];
    }

    private function getRecentData(?int $shopId, callable $shopFilter): array
    {
        $recentSales = Sale::with(['user', 'customer', 'reseller', 'shop'])
            ->when($shopId, $shopFilter)->latest()->take(10)->get();

        $recentRepairs = Repair::with(['customer', 'technician', 'shop'])
            ->when($shopId, $shopFilter)->latest()->take(10)->get();

        $lowStockProducts = Product::lowStock()
            ->with(['category', 'shop'])
            ->when($shopId, $shopFilter)->take(10)->get();

        $urgentSavTickets = SavTicket::open()->urgent()
            ->with(['customer', 'creator', 'shop'])
            ->when($shopId, $shopFilter)->latest()->take(5)->get();

        $topExpenseCategories = Expense::currentMonth()->approved()
            ->when($shopId, $shopFilter)
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->with('category')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->take(5)->get();

        $recentExpenses = Expense::with(['category', 'user', 'shop'])
            ->when($shopId, $shopFilter)->latest()->take(5)->get();

        $deliveredRepairs = Repair::whereIn('status', [
                Repair::STATUS_DELIVERED,
                Repair::STATUS_REPAIRED,
                Repair::STATUS_READY_FOR_PICKUP,
            ])
            ->when($shopId, $shopFilter)
            ->with(['customer', 'technician'])
            ->latest('repaired_at')->take(10)->get();

        return [$recentSales, $recentRepairs, $lowStockProducts, $urgentSavTickets,
                $topExpenseCategories, $recentExpenses, $deliveredRepairs];
    }

    private function getStockValue(?int $shopId): array
    {
        $stockShops = $shopId
            ? Shop::active()->where('id', $shopId)->get()
            : Shop::active()->get();

        $stockValueByShop = $stockShops->map(function ($shop) {
            $stockValue = Product::withoutGlobalScope('shop')
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->selectRaw('SUM(quantity_in_stock * purchase_price) as total_value')
                ->value('total_value') ?? 0;

            $productCount = Product::withoutGlobalScope('shop')
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->sum('quantity_in_stock');

            return ['shop' => $shop, 'stock_value' => $stockValue, 'product_count' => $productCount];
        });

        return [$stockValueByShop, $stockValueByShop->sum('stock_value')];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SavTicket;
use App\Models\Shop;
use App\Services\Reports\FinancialReportService;
use App\Services\Reports\RepairReportService;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\SavReportService;
use App\Services\Reports\StockReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $salesService,
        private readonly FinancialReportService $financialService,
        private readonly RepairReportService $repairService,
        private readonly StockReportService $stockService,
        private readonly SavReportService $savService,
    ) {}

    // ─────────────────────────────────────────────────────────
    //  Dashboard
    // ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $shops = Shop::active()->orderBy('name')->get();
        return view('admin.reports.index', compact('shops'));
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport des ventes
    // ─────────────────────────────────────────────────────────

    public function sales(Request $request)
    {
        $startDate  = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId     = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $categoryId = $request->get('category_id') ? (int) $request->get('category_id') : null;
        $productId  = $request->get('product_id') ? (int) $request->get('product_id') : null;

        // Filtre client unifié : "c_{id}" = particulier, "r_{id}" = revendeur
        $clientFilter = $request->get('client_filter') ?? '';
        $customerId   = null;
        $resellerId   = null;
        if (str_starts_with($clientFilter, 'c_')) {
            $customerId = (int) substr($clientFilter, 2) ?: null;
        } elseif (str_starts_with($clientFilter, 'r_')) {
            $resellerId = (int) substr($clientFilter, 2) ?: null;
        } elseif ($request->get('customer_id')) {
            // Rétro-compatibilité avec les anciens liens
            $customerId   = (int) $request->get('customer_id');
            $clientFilter = 'c_' . $customerId;
        }

        $shops      = Shop::active()->orderBy('name')->get();
        $customers  = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('first_name')->orderBy('last_name')->get();
        $resellers  = Reseller::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->whereHas('sales', fn($s) => $s->where('shop_id', $shopId)))
            ->orderBy('company_name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $products   = Product::withoutGlobalScope('shop')
            ->where('is_active', true)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('name')->get();

        [
            'totalSales'    => $totalSales,
            'totalRevenue'  => $totalRevenue,
            'totalPaid'     => $totalPaid,
            'totalCredit'   => $totalCredit,
            'totalDiscount' => $totalDiscount,
        ] = $this->salesService->getKpis($startDate, $endDate, $shopId, $customerId, $categoryId, $productId, $resellerId);

        $salesByDay        = $this->salesService->getByDay($startDate, $endDate, $shopId, $customerId, $categoryId, $productId, $resellerId);
        $salesByClientType = $this->salesService->getByClientType($startDate, $endDate, $shopId, $customerId, $categoryId, $productId, $resellerId);
        $salesByPayment    = $this->salesService->getByPayment($startDate, $endDate, $shopId, $customerId, $categoryId, $productId, $resellerId);
        $salesByUser       = $this->salesService->getByUser($startDate, $endDate, $shopId);
        $topProducts       = $this->salesService->getTopProducts($startDate, $endDate, $shopId, $customerId, $categoryId, $productId, 10, $resellerId);
        $topCustomers      = $this->salesService->getTopCustomers($startDate, $endDate, $shopId);
        $topResellers      = $this->salesService->getTopResellers($startDate, $endDate, $shopId);

        [
            'previousRevenue' => $previousRevenue,
            'previousSales'   => $previousSales,
            'revenueGrowth'   => $revenueGrowth,
        ] = $this->salesService->getPreviousPeriod($startDate, $endDate, $shopId, $totalRevenue);

        [
            'n1Revenue' => $n1Revenue,
            'n1Sales'   => $n1Sales,
            'n1Growth'  => $n1Growth,
        ] = $this->salesService->getN1($startDate, $endDate, $shopId, $totalRevenue);

        return view('admin.reports.sales', compact(
            'startDate', 'endDate', 'shops', 'shopId',
            'customerId', 'resellerId', 'clientFilter', 'categoryId', 'productId',
            'customers', 'resellers', 'categories', 'products',
            'totalSales', 'totalRevenue', 'totalPaid', 'totalCredit', 'totalDiscount',
            'salesByDay', 'salesByClientType', 'salesByPayment', 'salesByUser',
            'topProducts', 'topCustomers', 'topResellers',
            'previousRevenue', 'previousSales', 'revenueGrowth',
            'n1Revenue', 'n1Sales', 'n1Growth'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport des réparations
    // ─────────────────────────────────────────────────────────

    public function repairs(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $shops     = Shop::active()->orderBy('name')->get();

        [
            'totalRepairs'      => $totalRepairs,
            'totalRevenue'      => $totalRevenue,
            'averageRepairTime' => $averageRepairTime,
            'deliveredCount'    => $deliveredCount,
            'successRate'       => $successRate,
        ] = $this->repairService->getKpis($startDate, $endDate, $shopId);

        $repairsByStatus       = $this->repairService->getByStatus($startDate, $endDate, $shopId);
        $repairsByDay          = $this->repairService->getByDay($startDate, $endDate, $shopId);
        $repairsByDevice       = $this->repairService->getByDevice($startDate, $endDate, $shopId);
        $repairsByBrand        = $this->repairService->getByBrand($startDate, $endDate, $shopId);
        $technicianPerformance = $this->repairService->getTechnicianPerformance($startDate, $endDate, $shopId);
        $commonIssues          = $this->repairService->getCommonIssues($startDate, $endDate, $shopId);

        [
            'n1Repairs'   => $n1Repairs,
            'n1Revenue'   => $n1Revenue,
            'n1RepGrowth' => $n1RepGrowth,
        ] = $this->repairService->getN1($startDate, $endDate, $shopId, $totalRevenue);

        return view('admin.reports.repairs', compact(
            'startDate', 'endDate', 'shops', 'shopId',
            'totalRepairs', 'totalRevenue', 'averageRepairTime',
            'repairsByStatus', 'repairsByDay', 'repairsByDevice', 'repairsByBrand',
            'technicianPerformance', 'commonIssues', 'successRate', 'deliveredCount',
            'n1Repairs', 'n1Revenue', 'n1RepGrowth'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport du stock
    // ─────────────────────────────────────────────────────────

    public function stock(Request $request)
    {
        $shopId     = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $categoryId = $request->get('category_id') ? (int) $request->get('category_id') : null;
        $shops      = Shop::active()->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        [
            'totalStockValue'   => $totalStockValue,
            'totalSellingValue' => $totalSellingValue,
            'potentialProfit'   => $potentialProfit,
            'totalProducts'     => $totalProducts,
            'activeProducts'    => $activeProducts,
            'outOfStock'        => $outOfStock,
            'lowStock'          => $lowStock,
        ] = $this->stockService->getKpis($shopId, $categoryId);

        $stockByCategory = $this->stockService->getByCategory($shopId, $categoryId);
        $productsToOrder = $this->stockService->getProductsToOrder($shopId, $categoryId);
        $mostProfitable  = $this->stockService->getMostProfitable($shopId, $categoryId);
        $stockRotation   = $this->stockService->getStockRotation($shopId, $categoryId);
        $dormantProducts = $this->stockService->getDormantProducts($shopId, $categoryId);
        $recentMovements = $this->stockService->getRecentMovements($shopId);

        return view('admin.reports.stock', compact(
            'totalStockValue', 'totalSellingValue', 'potentialProfit',
            'totalProducts', 'activeProducts', 'outOfStock', 'lowStock',
            'stockByCategory', 'productsToOrder', 'mostProfitable',
            'stockRotation', 'dormantProducts', 'recentMovements',
            'shops', 'shopId', 'categories', 'categoryId'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport financier
    // ─────────────────────────────────────────────────────────

    public function financial(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $shops     = Shop::active()->orderBy('name')->get();

        [
            'savRefunds'        => $savRefunds,
            'savExchangeLosses' => $savExchangeLosses,
            'savExchangeGains'  => $savExchangeGains,
            'savTotalImpact'    => $savTotalImpact,
            'savStats'          => $savStats,
        ] = $this->financialService->getSavImpact($startDate, $endDate, $shopId);

        [
            'salesRevenue'       => $salesRevenue,
            'repairsRevenue'     => $repairsRevenue,
            'totalRevenue'       => $totalRevenue,
            'netRevenue'         => $netRevenue,
            'totalCashCollected' => $totalCashCollected,
        ] = $this->financialService->getRevenue($startDate, $endDate, $shopId, $savExchangeGains, $savRefunds, $savExchangeLosses);

        [
            'totalExpenses'           => $totalExpenses,
            'expensesByCategory'      => $expensesByCategory,
            'expensesByPaymentMethod' => $expensesByPaymentMethod,
        ] = $this->financialService->getExpenses($startDate, $endDate, $shopId);

        [
            'costOfGoodsSold'      => $costOfGoodsSold,
            'grossProfit'          => $grossProfit,
            'profitMargin'         => $profitMargin,
            'technicianCommission' => $technicianCommission,
            'netProfit'            => $netProfit,
            'finalNetProfit'       => $finalNetProfit,
        ] = $this->financialService->getMargin(
            $startDate, $endDate, $shopId,
            $salesRevenue, $repairsRevenue, $savTotalImpact, $totalExpenses
        );

        ['salesCredit' => $salesCredit, 'resellerDebt' => $resellerDebt] =
            $this->financialService->getCredit($startDate, $endDate, $shopId);

        ['cashIn' => $cashIn, 'cashOut' => $cashOut] =
            $this->financialService->getCashFlow($startDate, $endDate, $shopId);

        $dates            = $this->financialService->getDailyBreakdown($startDate, $endDate, $shopId);
        $revenueByPayment = $this->financialService->getByPayment($startDate, $endDate, $shopId);
        $cashRegisters    = $this->financialService->getCashRegisters($startDate, $endDate, $shopId);

        return view('admin.reports.financial', compact(
            'startDate', 'endDate', 'shops', 'shopId',
            'salesRevenue', 'repairsRevenue', 'totalRevenue', 'netRevenue',
            'savStats', 'savTotalImpact',
            'salesCredit', 'resellerDebt',
            'cashIn', 'cashOut',
            'totalCashCollected',
            'dates', 'revenueByPayment', 'cashRegisters',
            'costOfGoodsSold', 'grossProfit', 'profitMargin', 'technicianCommission', 'netProfit',
            'totalExpenses', 'expensesByCategory', 'expensesByPaymentMethod', 'finalNetProfit'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport S.A.V
    // ─────────────────────────────────────────────────────────

    public function sav(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $savType      = $request->get('sav_type');
        $customerType = $request->get('customer_type');
        $shops        = Shop::active()->orderBy('name')->get();

        [
            'totalTickets'  => $totalTickets,
            'openTickets'   => $openTickets,
            'closedTickets' => $closedTickets,
        ] = $this->savService->getKpis($startDate, $endDate, $shopId, $savType, $customerType);

        [
            'totalRefunds'        => $totalRefunds,
            'totalExchangeLosses' => $totalExchangeLosses,
            'totalExchangeGains'  => $totalExchangeGains,
        ] = $this->savService->getFinancialImpact($startDate, $endDate, $shopId);

        $ticketsByType     = $this->savService->getByType($startDate, $endDate, $shopId, $savType, $customerType);
        $ticketsByStatus   = $this->savService->getByStatus($startDate, $endDate, $shopId, $savType, $customerType);
        $ticketsByPriority = $this->savService->getByPriority($startDate, $endDate, $shopId, $savType, $customerType);
        $ticketsByDay      = $this->savService->getByDay($startDate, $endDate, $shopId, $savType, $customerType);
        $ticketsByCreator  = $this->savService->getByCreator($startDate, $endDate, $shopId, $savType, $customerType);

        $salesWithMostSav     = $this->savService->getSalesWithMostSav($startDate, $endDate, $shopId);
        $problematicProducts  = $this->savService->getProblematicProducts($startDate, $endDate, $shopId);
        $customersWithMostSav = $this->savService->getCustomersWithMostSav($startDate, $endDate, $shopId);
        $suspiciousRefunds    = $this->savService->getSuspiciousRefunds($startDate, $endDate, $shopId);
        $savByVendor          = $this->savService->getSavByVendor($startDate, $endDate, $shopId);
        $avgResolutionTime    = $this->savService->getAvgResolutionTime($startDate, $endDate, $shopId);
        $oldOpenTickets       = $this->savService->getOldOpenTickets($shopId);
        $recentRefunds        = $this->savService->getRecentRefunds($startDate, $endDate, $shopId);

        [
            'previousTotalTickets' => $previousTotalTickets,
            'previousRefunds'      => $previousRefunds,
            'ticketGrowth'         => $ticketGrowth,
            'refundGrowth'         => $refundGrowth,
        ] = $this->savService->getPreviousPeriod($startDate, $endDate, $shopId, $totalTickets, $totalRefunds);

        return view('admin.reports.sav', compact(
            'startDate', 'endDate', 'shops', 'shopId', 'savType', 'customerType',
            'totalTickets', 'openTickets', 'closedTickets',
            'totalRefunds', 'totalExchangeLosses', 'totalExchangeGains',
            'ticketsByType', 'ticketsByStatus', 'ticketsByPriority', 'ticketsByDay',
            'ticketsByCreator', 'salesWithMostSav', 'problematicProducts',
            'customersWithMostSav', 'suspiciousRefunds', 'savByVendor',
            'avgResolutionTime', 'oldOpenTickets', 'recentRefunds',
            'previousTotalTickets', 'previousRefunds', 'ticketGrowth', 'refundGrowth'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport des clients (pas de service dédié)
    // ─────────────────────────────────────────────────────────

    public function customers(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $customerType = $request->get('customer_type');
        $shops        = Shop::active()->orderBy('name')->get();

        $customerQuery = Customer::withoutGlobalScope('shop');
        if ($shopId) $customerQuery->where('shop_id', $shopId);

        $totalCustomers  = (clone $customerQuery)->count();
        $newCustomers    = (clone $customerQuery)->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count();
        $activeCustomers = (clone $customerQuery)->whereHas('sales', function ($q) use ($startDate, $endDate, $shopId) {
            $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
            if ($shopId) $q->where('shop_id', $shopId);
        })->count();

        $topCustomersByRevenue = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->withSum(['sales' => function ($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) $q->where('shop_id', $shopId);
            }], 'total_amount')
            ->withCount(['sales' => function ($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) $q->where('shop_id', $shopId);
            }])
            ->orderByDesc('sales_sum_total_amount')
            ->limit(20)->get();

        $loyalCustomers = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->withCount(['sales' => function ($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) $q->where('shop_id', $shopId);
            }])
            ->having('sales_count', '>=', 3)
            ->orderByDesc('sales_count')->get();

        $customersWithRepairs = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->withCount(['repairs' => function ($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) $q->where('shop_id', $shopId);
            }])
            ->having('repairs_count', '>', 0)
            ->orderByDesc('repairs_count')->limit(20)->get();

        $customersByDay = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $resellerQuery = Reseller::query();
        if ($shopId) {
            $resellerQuery->whereHas('sales', fn($q) => $q->withoutGlobalScope('shop')->where('shop_id', $shopId));
        }

        $totalResellers    = (clone $resellerQuery)->count();
        $resellersWithDebt = (clone $resellerQuery)->where('current_debt', '>', 0)->count();
        $totalResellerDebt = (clone $resellerQuery)->sum('current_debt');

        $topResellers = Reseller::query()
            ->when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))
            ->withSum(['sales' => function ($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) $q->where('shop_id', $shopId);
            }], 'total_amount')
            ->withCount(['sales' => function ($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) $q->where('shop_id', $shopId);
            }])
            ->orderByDesc('sales_sum_total_amount')->limit(10)->get();

        return view('admin.reports.customers', compact(
            'startDate', 'endDate', 'shops', 'shopId', 'customerType',
            'totalCustomers', 'newCustomers', 'activeCustomers',
            'topCustomersByRevenue', 'loyalCustomers', 'customersWithRepairs', 'customersByDay',
            'totalResellers', 'resellersWithDebt', 'totalResellerDebt', 'topResellers'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  PDF EXPORTS
    // ─────────────────────────────────────────────────────────

    public function salesPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $shop      = $shopId ? Shop::find($shopId) : null;

        [
            'totalSales'    => $totalSales,
            'totalRevenue'  => $totalRevenue,
            'totalPaid'     => $totalPaid,
            'totalCredit'   => $totalCredit,
            'totalDiscount' => $totalDiscount,
        ] = $this->salesService->getKpis($startDate, $endDate, $shopId);

        $salesByPayment    = $this->salesService->getByPayment($startDate, $endDate, $shopId);
        $salesByClientType = $this->salesService->getByClientType($startDate, $endDate, $shopId);
        $salesByDay        = $this->salesService->getByDay($startDate, $endDate, $shopId);

        // PDF orders top products by revenue, not quantity
        $topProducts = SaleItem::whereHas('sale', fn($sq) =>
                $sq->withoutGlobalScope('shop')
                   ->where('payment_status', '!=', 'cancelled')
                   ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                   ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            )
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_id')->orderByDesc('total_revenue')->limit(15)->get();

        $cancelledSales = Sale::withoutGlobalScope('shop')
            ->where('payment_status', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->with(['customer', 'reseller', 'user', 'items.product'])
            ->orderByDesc('created_at')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.sales-pdf', compact(
            'startDate', 'endDate', 'shop',
            'totalSales', 'totalRevenue', 'totalPaid', 'totalCredit', 'totalDiscount',
            'salesByPayment', 'salesByClientType', 'topProducts', 'salesByDay', 'cancelledSales'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_ventes_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function repairsPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $shop      = $shopId ? Shop::find($shopId) : null;

        [
            'totalRepairs'      => $totalRepairs,
            'totalRevenue'      => $totalRevenue,
            'deliveredCount'    => $deliveredCount,
            'successRate'       => $successRate,
            'averageRepairTime' => $avgRepairTime,
        ] = $this->repairService->getKpis($startDate, $endDate, $shopId);

        $repairsByStatus = $this->repairService->getByStatus($startDate, $endDate, $shopId);
        $repairsByDevice = $this->repairService->getByDevice($startDate, $endDate, $shopId);
        $repairsByBrand  = $this->repairService->getByBrand($startDate, $endDate, $shopId);
        $techPerformance = $this->repairService->getTechnicianPerformancePdf($startDate, $endDate, $shopId);
        $repairsByDay    = $this->repairService->getByDay($startDate, $endDate, $shopId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.repairs-pdf', compact(
            'startDate', 'endDate', 'shop',
            'totalRepairs', 'totalRevenue', 'deliveredCount', 'successRate', 'avgRepairTime',
            'repairsByStatus', 'repairsByDevice', 'repairsByBrand', 'techPerformance', 'repairsByDay'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_reparations_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function stockPdf(Request $request)
    {
        $shopId     = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $categoryId = $request->get('category_id') ? (int) $request->get('category_id') : null;
        $shop       = $shopId ? Shop::find($shopId) : null;
        $category   = $categoryId ? Category::find($categoryId) : null;

        [
            'totalStockValue'   => $totalStockValue,
            'totalSellingValue' => $totalSellingValue,
            'totalProducts'     => $totalProducts,
            'outOfStock'        => $outOfStock,
            'lowStock'          => $lowStock,
        ] = $this->stockService->getKpis($shopId, $categoryId);

        $stockByCategory = $this->stockService->getByCategory($shopId, $categoryId);
        $productsToOrder = $this->stockService->getProductsToOrder($shopId, $categoryId);
        $mostProfitable  = $this->stockService->getMostProfitablePdf($shopId, $categoryId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.stock-pdf', compact(
            'shop', 'category',
            'totalStockValue', 'totalSellingValue', 'totalProducts', 'outOfStock', 'lowStock',
            'stockByCategory', 'productsToOrder', 'mostProfitable'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_stock_' . date('Y-m-d') . '.pdf');
    }

    public function financialPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $shop      = $shopId ? Shop::find($shopId) : null;

        [
            'savRefunds'        => $savRefunds,
            'savExchangeLosses' => $savExchangeLosses,
            'savExchangeGains'  => $savExchangeGains,
            'savTotalImpact'    => $savTotalImpact,
        ] = $this->financialService->getSavImpact($startDate, $endDate, $shopId);

        [
            'salesRevenue'   => $salesRevenue,
            'repairsRevenue' => $repairsRevenue,
            'netRevenue'     => $netRevenue,
        ] = $this->financialService->getRevenue($startDate, $endDate, $shopId, $savExchangeGains, $savRefunds, $savExchangeLosses);

        ['totalExpenses' => $totalExpenses, 'expensesByCategory' => $expensesByCategory] =
            $this->financialService->getExpenses($startDate, $endDate, $shopId);

        [
            'costOfGoodsSold'      => $costOfGoodsSold,
            'grossProfit'          => $grossProfit,
            'profitMargin'         => $profitMargin,
            'technicianCommission' => $technicianCommission,
            'finalNetProfit'       => $finalNetProfit,
        ] = $this->financialService->getMargin(
            $startDate, $endDate, $shopId,
            $salesRevenue, $repairsRevenue, $savTotalImpact, $totalExpenses
        );

        $revenueByPayment = $this->financialService->getByPayment($startDate, $endDate, $shopId);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.financial-pdf', compact(
            'startDate', 'endDate', 'shop',
            'salesRevenue', 'repairsRevenue', 'netRevenue',
            'savRefunds', 'savExchangeLosses', 'savExchangeGains', 'savTotalImpact',
            'totalExpenses', 'expensesByCategory', 'revenueByPayment',
            'costOfGoodsSold', 'grossProfit', 'profitMargin', 'technicianCommission', 'finalNetProfit'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_financier_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function customersPdf(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $customerType = $request->get('customer_type');
        $shop         = $shopId ? Shop::find($shopId) : null;

        $topCustomersByRevenue = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->withSum(['sales' => fn($q) =>
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                  ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ], 'total_amount')
            ->withCount(['sales' => fn($q) =>
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                  ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ])
            ->orderByDesc('sales_sum_total_amount')->limit(20)->get();

        $topResellers = Reseller::query()
            ->when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))
            ->withSum(['sales' => fn($q) =>
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                  ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ], 'total_amount')
            ->withCount(['sales' => fn($q) =>
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                  ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ])
            ->orderByDesc('sales_sum_total_amount')->limit(10)->get();

        $totalCustomers    = Customer::withoutGlobalScope('shop')->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count();
        $totalResellers    = Reseller::when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))->count();
        $totalResellerDebt = Reseller::when($shopId, fn($q) => $q->whereHas('sales', fn($sq) => $sq->withoutGlobalScope('shop')->where('shop_id', $shopId)))->sum('current_debt');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.customers-pdf', compact(
            'startDate', 'endDate', 'shop', 'customerType',
            'totalCustomers', 'totalResellers', 'totalResellerDebt',
            'topCustomersByRevenue', 'topResellers'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_clients_' . $startDate . '_' . $endDate . '.pdf');
    }

    public function savPdf(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $savType      = $request->get('sav_type');
        $customerType = $request->get('customer_type');
        $shop         = $shopId ? Shop::find($shopId) : null;

        [
            'totalTickets' => $totalTickets,
            'openTickets'  => $openTickets,
        ] = $this->savService->getKpis($startDate, $endDate, $shopId, $savType, $customerType);

        [
            'totalRefunds'        => $totalRefunds,
            'totalExchangeLosses' => $totalExchangeLosses,
        ] = $this->savService->getFinancialImpact($startDate, $endDate, $shopId);

        $ticketsByType   = $this->savService->getByType($startDate, $endDate, $shopId, $savType, $customerType);
        $ticketsByStatus = $this->savService->getByStatus($startDate, $endDate, $shopId, $savType, $customerType);
        $recentRefunds   = $this->savService->getRecentRefunds($startDate, $endDate, $shopId);
        $ticketsByCreator= $this->savService->getByCreatorPdf($startDate, $endDate, $shopId, $savType, $customerType);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.sav-pdf', compact(
            'startDate', 'endDate', 'shop', 'savType', 'customerType',
            'totalTickets', 'openTickets', 'totalRefunds', 'totalExchangeLosses',
            'ticketsByType', 'ticketsByStatus', 'recentRefunds', 'ticketsByCreator'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_sav_' . $startDate . '_' . $endDate . '.pdf');
    }

    // ─────────────────────────────────────────────────────────
    //  Export PDF — Ventes par produit avec marges
    // ─────────────────────────────────────────────────────────

    public function salesProductsPdf(Request $request)
    {
        $startDate  = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId     = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $customerId = $request->get('customer_id') ? (int) $request->get('customer_id') : null;
        $categoryId = $request->get('category_id') ? (int) $request->get('category_id') : null;
        $productId  = $request->get('product_id') ? (int) $request->get('product_id') : null;
        $shop       = $shopId ? Shop::find($shopId) : null;

        $itemsQuery = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNull('sales.deleted_at')
            ->where('sales.payment_status', '!=', 'cancelled')
            ->whereBetween('sales.created_at', [$startDate, $endDate . ' 23:59:59']);

        if ($shopId)     $itemsQuery->where('sales.shop_id', $shopId);
        if ($customerId) $itemsQuery->where('sales.customer_id', $customerId);
        if ($categoryId) $itemsQuery->where('products.category_id', $categoryId);
        if ($productId)  $itemsQuery->where('sale_items.product_id', $productId);

        $rows = $itemsQuery
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.total_price * sales.total_amount / NULLIF(sales.subtotal, 0)) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * products.purchase_price) as total_cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($row) {
                $row->margin     = $row->total_revenue - $row->total_cost;
                $row->margin_pct = $row->total_revenue > 0
                    ? round($row->margin / $row->total_revenue * 100, 1)
                    : 0;
                return $row;
            });

        $totals = [
            'qty'        => $rows->sum('total_qty'),
            'revenue'    => $rows->sum('total_revenue'),
            'cost'       => $rows->sum('total_cost'),
            'margin'     => $rows->sum('margin'),
            'margin_pct' => $rows->sum('total_revenue') > 0
                ? round($rows->sum('margin') / $rows->sum('total_revenue') * 100, 1)
                : 0,
        ];

        $cancelledSales = Sale::withoutGlobalScope('shop')
            ->where('payment_status', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->with(['items.product', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.sales-products-pdf', compact(
            'rows', 'totals', 'startDate', 'endDate', 'shop', 'cancelledSales'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('ventes_produits_' . $startDate . '_' . $endDate . '.pdf');
    }

    // ─────────────────────────────────────────────────────────
    //  Export CSV
    // ─────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $type      = $request->get('type', 'sales');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        return match ($type) {
            'sales'   => $this->exportSales($startDate, $endDate),
            'repairs' => $this->exportRepairs($startDate, $endDate),
            'stock'   => $this->exportStock(),
            'sav'     => $this->exportSav($startDate, $endDate),
            default   => back()->with('error', 'Type d\'export invalide.'),
        };
    }

    protected function exportSales(string $startDate, string $endDate)
    {
        $filename = 'ventes_' . $startDate . '_' . $endDate . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['N° Facture', 'Date', 'Client', 'Type', 'Produits', 'Montant', 'Payé', 'Mode', 'Caissier']);

            Sale::with(['customer', 'reseller', 'user', 'items.product'])
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->lazyById()
                ->each(function ($sale) use ($file) {
                    $client   = $sale->client_type === 'customer'
                        ? ($sale->customer ? $sale->customer->full_name : 'Client')
                        : ($sale->reseller ? $sale->reseller->company_name : 'Revendeur');
                    $products = $sale->items->map(fn($i) => ($i->product->name ?? '?') . ' x' . $i->quantity)->join(', ');

                    fputcsv($file, [
                        $sale->invoice_number,
                        $sale->created_at->format('d/m/Y H:i'),
                        $client,
                        $sale->client_type === 'customer' ? 'Particulier' : 'Revendeur',
                        $products,
                        $sale->total_amount,
                        $sale->amount_paid,
                        $sale->payment_method,
                        $sale->user->name ?? '',
                    ]);
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportRepairs(string $startDate, string $endDate)
    {
        $filename = 'reparations_' . $startDate . '_' . $endDate . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['N° Ticket', 'Date', 'Client', 'Appareil', 'Problème', 'Statut', 'Technicien', 'Coût', 'Payé']);

            Repair::with(['customer', 'technician', 'creator'])
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->lazyById()
                ->each(function ($repair) use ($file) {
                    fputcsv($file, [
                        $repair->repair_number,
                        $repair->created_at->format('d/m/Y H:i'),
                        $repair->customer->full_name ?? '',
                        $repair->device_brand . ' ' . $repair->device_model,
                        $repair->reported_issue,
                        $repair->status,
                        $repair->technician->name ?? '',
                        $repair->final_cost ?? $repair->estimated_cost,
                        $repair->amount_paid > 0 ? 'Oui' : 'Non',
                    ]);
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportStock()
    {
        $filename = 'stock_' . date('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['SKU', 'Produit', 'Catégorie', 'Stock', 'Prix Achat', 'Prix Minimum', 'Valeur Stock']);

            Product::with('category')->lazyById()->each(function ($product) use ($file) {
                fputcsv($file, [
                    $product->sku,
                    $product->name,
                    $product->category->name ?? '',
                    $product->quantity_in_stock,
                    $product->purchase_price,
                    $product->normal_price,
                    $product->quantity_in_stock * $product->purchase_price,
                ]);
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportSav(string $startDate, string $endDate)
    {
        $filename = 'sav_audit_' . $startDate . '_' . $endDate . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'N° Ticket', 'Date Création', 'Date Résolution', 'Type', 'Statut', 'Priorité',
                'Client', 'Téléphone Client', 'Produit', 'N° Vente Liée', 'Date Vente',
                'Vendeur Vente', 'Créé Par', 'Assigné À', 'Problème Signalé', 'Résolution',
                'Montant Remboursé', 'Différence Échange', 'Délai Résolution (h)', 'Même Employé Vente/SAV',
            ]);

            SavTicket::with(['customer', 'sale.user', 'product', 'creator', 'assignedUser'])
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->lazyById()
                ->each(function ($ticket) use ($file) {
                    $sameEmployee    = $ticket->sale && $ticket->sale->user_id === $ticket->created_by ? 'OUI' : 'NON';
                    $resolutionDelay = $ticket->resolved_at
                        ? $ticket->created_at->diffInHours($ticket->resolved_at)
                        : '';

                    fputcsv($file, [
                        $ticket->ticket_number,
                        $ticket->created_at->format('d/m/Y H:i'),
                        $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : '',
                        $ticket->type_name,
                        $ticket->status_name,
                        $ticket->priority_name,
                        $ticket->customer->full_name ?? 'N/A',
                        $ticket->customer->phone ?? '',
                        $ticket->product->name ?? $ticket->product_name ?? 'N/A',
                        $ticket->sale->invoice_number ?? '',
                        $ticket->sale ? $ticket->sale->created_at->format('d/m/Y H:i') : '',
                        $ticket->sale->user->name ?? '',
                        $ticket->creator->name ?? '',
                        $ticket->assignedUser->name ?? '',
                        $ticket->issue_description,
                        $ticket->resolution_notes ?? '',
                        $ticket->refund_amount ?? 0,
                        $ticket->exchange_difference ?? 0,
                        $resolutionDelay,
                        $sameEmployee,
                    ]);
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─────────────────────────────────────────────────────────
    //  Relevé de créance revendeur
    // ─────────────────────────────────────────────────────────

    public function resellerStatement(Request $request, Reseller $reseller)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $shops     = Shop::active()->orderBy('name')->get();

        $salesQuery = Sale::withoutGlobalScope('shop')
            ->with(['items.product', 'shop', 'user'])
            ->where('reseller_id', $reseller->id)
            ->whereIn('payment_status', ['credit', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) $salesQuery->where('shop_id', $shopId);

        $sales = $salesQuery->orderBy('created_at')->get();

        $payments = ResellerPayment::where('reseller_id', $reseller->id)
            ->with(['user', 'sale'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at')->get();

        $totalAmount         = $sales->sum('total_amount');
        $totalDiscount       = $sales->sum('discount_amount');
        $totalPaid           = $sales->sum('amount_paid');
        $totalOutstanding    = $sales->sum('amount_due');
        $totalPaymentsAmount = $payments->sum('amount');

        return view('admin.customers.statement', compact(
            'reseller', 'sales', 'payments',
            'startDate', 'endDate', 'shops', 'shopId',
            'totalAmount', 'totalPaid', 'totalOutstanding', 'totalDiscount',
            'totalPaymentsAmount'
        ));
    }

    public function resellerStatementPdf(Request $request, Reseller $reseller)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id') ? (int) $request->get('shop_id') : null;

        $salesQuery = Sale::withoutGlobalScope('shop')
            ->with(['items.product', 'shop', 'user'])
            ->where('reseller_id', $reseller->id)
            ->whereIn('payment_status', ['credit', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) $salesQuery->where('shop_id', $shopId);

        $sales = $salesQuery->orderBy('created_at')->get();

        $payments = ResellerPayment::where('reseller_id', $reseller->id)
            ->with(['user'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at')->get();

        $totalAmount         = $sales->sum('total_amount');
        $totalDiscount       = $sales->sum('discount_amount');
        $totalPaid           = $sales->sum('amount_paid');
        $totalOutstanding    = $sales->sum('amount_due');
        $totalPaymentsAmount = $payments->sum('amount');

        $companyName    = \App\Models\Setting::get('company_name', 'EGREGORE BUSINESS');
        $companyAddress = \App\Models\Setting::get('company_address', '');
        $companyPhone   = \App\Models\Setting::get('company_phone', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.customers.statement-pdf', compact(
            'reseller', 'sales', 'payments',
            'startDate', 'endDate', 'shopId',
            'totalAmount', 'totalPaid', 'totalOutstanding', 'totalDiscount',
            'totalPaymentsAmount',
            'companyName', 'companyAddress', 'companyPhone'
        ))->setPaper('a4', 'portrait');

        $filename = 'releve-creance-' . strtolower(str_replace(' ', '-', $reseller->company_name)) . '-' . $endDate . '.pdf';
        return $pdf->download($filename);
    }
}

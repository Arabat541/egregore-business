<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SavTicket;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rapports et analyses pour l'administrateur
 * Avec filtre par boutique
 */
class ReportController extends Controller
{
    /**
     * Tableau de bord des rapports
     */
    public function index(Request $request)
    {
        $shops = Shop::active()->orderBy('name')->get();
        return view('admin.reports.index', compact('shops'));
    }

    /**
     * Rapport des ventes
     */
    public function sales(Request $request)
    {
        $startDate  = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId     = $request->get('shop_id');
        $customerId = $request->get('customer_id');
        $categoryId = $request->get('category_id');
        $productId  = $request->get('product_id');

        $shops      = Shop::active()->orderBy('name')->get();
        $customers  = Customer::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('first_name')->orderBy('last_name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $products   = Product::withoutGlobalScope('shop')
            ->where('is_active', true)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('name')->get();

        // ── Closure : applique tous les filtres sur une query Sale ────────
        $saleBase = function () use ($startDate, $endDate, $shopId, $customerId, $categoryId, $productId) {
            $q = Sale::withoutGlobalScope('shop')
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

            if ($shopId)     $q->where('shop_id', $shopId);
            if ($customerId) $q->where('customer_id', $customerId);
            if ($categoryId) $q->whereHas('items.product', fn($sq) => $sq->where('category_id', $categoryId));
            if ($productId)  $q->whereHas('items', fn($sq) => $sq->where('product_id', $productId));

            return $q;
        };

        // ── KPIs ─────────────────────────────────────────────────────────
        $salesQuery   = $saleBase();
        $totalSales   = (clone $salesQuery)->count();
        $totalRevenue = (clone $salesQuery)->sum('total_amount');
        $totalPaid    = (clone $salesQuery)->sum('amount_paid');
        $totalCredit  = (clone $salesQuery)->where('payment_status', 'credit')->sum('total_amount');
        // Ajouter les pièces de rechange des réparations (non incluses dans Sale si workflow caissier)
        $repairPartsQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $repairPartsQuery->where('shop_id', $shopId);
        }
        $totalRevenue += $repairPartsQuery->sum('parts_cost');
        $totalPaid    += (clone $repairPartsQuery)->sum('parts_cost');

        // ── Ventes par jour ───────────────────────────────────────────────
        $salesByDay = (clone $salesQuery)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Ventes par type de client ─────────────────────────────────────
        $salesByClientType = (clone $salesQuery)
            ->select('client_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('client_type')
            ->get();

        // ── Ventes par mode de paiement ───────────────────────────────────
        $salesByPayment = (clone $salesQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // ── Ventes par caissière ──────────────────────────────────────────
        $salesByUser = (clone $salesQuery)
            ->with('user')
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('user_id')
            ->get();

        // ── Ajouter pièces de réparation (workflow caissier) aux breakdowns ─
        $repairsBreakdownBase = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('parts_cost', '>', 0);
        if ($shopId) $repairsBreakdownBase->where('shop_id', $shopId);

        // Par jour
        $repairsByDay = (clone $repairsBreakdownBase)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(parts_cost) as total'))
            ->groupBy('date')->orderBy('date')->get();
        foreach ($repairsByDay as $rbd) {
            $found = $salesByDay->first(fn($d) => $d->date == $rbd->date);
            if ($found) { $found->total += $rbd->total; $found->count += $rbd->count; }
            else { $salesByDay->push($rbd); }
        }
        $salesByDay = $salesByDay->sortBy('date')->values();

        // Par type de client (les réparations sont toujours 'customer')
        $repairsPartsTotal = (clone $repairsBreakdownBase)->sum('parts_cost');
        $repairsPartsCount = (clone $repairsBreakdownBase)->count();
        $customerType = $salesByClientType->first(fn($t) => $t->client_type === 'customer');
        if ($customerType) {
            $customerType->total += $repairsPartsTotal;
            $customerType->count += $repairsPartsCount;
        } else {
            $salesByClientType->push((object)['client_type' => 'customer', 'count' => $repairsPartsCount, 'total' => $repairsPartsTotal]);
        }

        // Par mode de paiement
        $repairsByPayment = (clone $repairsBreakdownBase)
            ->select('payment_method', DB::raw('SUM(parts_cost) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')->get();
        foreach ($repairsByPayment as $rbp) {
            $found = $salesByPayment->first(fn($p) => $p->payment_method == $rbp->payment_method);
            if ($found) { $found->total += $rbp->total; $found->count += $rbp->count; }
            else { $salesByPayment->push((object)['payment_method' => $rbp->payment_method, 'count' => $rbp->count, 'total' => $rbp->total]); }
        }

        // Par vendeur (caissier ayant créé la réparation)
        $repairsByCreator = (clone $repairsBreakdownBase)
            ->with('creator')
            ->select('created_by', DB::raw('SUM(parts_cost) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('created_by')->get();
        foreach ($repairsByCreator as $rbc) {
            $found = $salesByUser->first(fn($u) => $u->user_id == $rbc->created_by);
            if ($found) { $found->total += $rbc->total; $found->count += $rbc->count; }
            else { $salesByUser->push((object)['user_id' => $rbc->created_by, 'user' => $rbc->creator, 'count' => $rbc->count, 'total' => $rbc->total]); }
        }

        // ── Top 10 produits ───────────────────────────────────────────────
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate, $shopId, $customerId) {
                $q->withoutGlobalScope('shop')
                  ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId)     $q->where('shop_id', $shopId);
                if ($customerId) $q->where('customer_id', $customerId);
            })
            ->when($categoryId, fn($q) => $q->whereHas('product', fn($sq) => $sq->where('category_id', $categoryId)))
            ->when($productId,  fn($q) => $q->where('product_id', $productId))
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ── Top 10 clients ────────────────────────────────────────────────
        $topCustomers = (clone $salesQuery)
            ->whereNotNull('customer_id')
            ->with('customer')
            ->select('customer_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Top 10 revendeurs ─────────────────────────────────────────────
        $topResellers = (clone $salesQuery)
            ->whereNotNull('reseller_id')
            ->with('reseller')
            ->select('reseller_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('reseller_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Comparaison période précédente ────────────────────────────────
        $daysDiff      = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $previousStart = Carbon::parse($startDate)->subDays($daysDiff)->format('Y-m-d');
        $previousEnd   = Carbon::parse($startDate)->subDay()->format('Y-m-d');

        $prevQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$previousStart, $previousEnd . ' 23:59:59']);
        if ($shopId) $prevQuery->where('shop_id', $shopId);

        $previousRevenue = (clone $prevQuery)->sum('total_amount');
        $previousSales   = (clone $prevQuery)->count();
        $revenueGrowth   = $previousRevenue > 0
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        // ── Comparaison N-1 ───────────────────────────────────────────────
        $n1Start = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $n1End   = Carbon::parse($endDate)->subYear()->format('Y-m-d');

        $n1Query = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$n1Start, $n1End . ' 23:59:59']);
        if ($shopId) $n1Query->where('shop_id', $shopId);

        $n1Revenue = (clone $n1Query)->sum('total_amount');
        $n1Sales   = (clone $n1Query)->count();
        $n1Growth  = $n1Revenue > 0
            ? round((($totalRevenue - $n1Revenue) / $n1Revenue) * 100, 1)
            : null;

        return view('admin.reports.sales', compact(
            'startDate', 'endDate', 'shops', 'shopId',
            'customerId', 'categoryId', 'productId',
            'customers', 'categories', 'products',
            'totalSales', 'totalRevenue', 'totalPaid', 'totalCredit',
            'salesByDay', 'salesByClientType', 'salesByPayment', 'salesByUser',
            'topProducts', 'topCustomers', 'topResellers',
            'previousRevenue', 'previousSales', 'revenueGrowth',
            'n1Revenue', 'n1Sales', 'n1Growth'
        ));
    }

    /**
     * Rapport des réparations
     */
    public function repairs(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId = $request->get('shop_id');
        $shops = Shop::active()->orderBy('name')->get();

        // Statistiques globales avec filtre boutique
        $repairsQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        
        if ($shopId) {
            $repairsQuery->where('shop_id', $shopId);
        }
        
        $totalRepairs = (clone $repairsQuery)->count();
        // CA Réparations = main d'œuvre uniquement (les pièces sont des ventes séparées)
        $totalRevenue = (clone $repairsQuery)->sum('labor_cost');
        $averageRepairTime = (clone $repairsQuery)->whereNotNull('repaired_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, repaired_at)) as avg_hours')
            ->value('avg_hours');

        // Réparations par statut
        $statusQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $statusQuery->where('shop_id', $shopId);
        }
        $repairsByStatus = $statusQuery
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Réparations par jour
        $dayQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $dayQuery->where('shop_id', $shopId);
        }
        $repairsByDay = $dayQuery
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(labor_cost) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Réparations par type d'appareil
        $deviceQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $deviceQuery->where('shop_id', $shopId);
        }
        $repairsByDevice = $deviceQuery
            ->select('device_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(labor_cost) as total'))
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get();

        // Réparations par marque
        $brandQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('device_brand');
        if ($shopId) {
            $brandQuery->where('shop_id', $shopId);
        }
        $repairsByBrand = $brandQuery
            ->select('device_brand', DB::raw('COUNT(*) as count'))
            ->groupBy('device_brand')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Performance des techniciens
        $techQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('technician_id');
        if ($shopId) {
            $techQuery->where('shop_id', $shopId);
        }
        $technicianPerformance = $techQuery
            ->with('technician')
            ->select(
                'technician_id',
                DB::raw('COUNT(*) as total_repairs'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed'),
                DB::raw('AVG(CASE WHEN repaired_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, diagnosis_at, repaired_at) END) as avg_repair_hours')
            )
            ->groupBy('technician_id')
            ->get();

        // Problèmes les plus fréquents
        $issuesQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $issuesQuery->where('shop_id', $shopId);
        }
        $commonIssues = $issuesQuery
            ->select('reported_issue', DB::raw('COUNT(*) as count'))
            ->groupBy('reported_issue')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Taux de réussite (livrées / total)
        $deliveredQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', 'delivered');
        if ($shopId) {
            $deliveredQuery->where('shop_id', $shopId);
        }
        $deliveredCount = $deliveredQuery->count();
        $successRate = $totalRepairs > 0 ? round(($deliveredCount / $totalRepairs) * 100, 1) : 0;

        // ── Comparaison N-1 (même période l'année dernière) ──────────────
        $n1Start = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $n1End   = Carbon::parse($endDate)->subYear()->format('Y-m-d');

        $n1RepQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$n1Start, $n1End . ' 23:59:59']);
        if ($shopId) $n1RepQuery->where('shop_id', $shopId);

        $n1Repairs  = (clone $n1RepQuery)->count();
        $n1Revenue  = (clone $n1RepQuery)->sum('labor_cost');
        $n1RepGrowth = $n1Revenue > 0
            ? round((($totalRevenue - $n1Revenue) / $n1Revenue) * 100, 1)
            : null;

        return view('admin.reports.repairs', compact(
            'startDate', 'endDate', 'shops', 'shopId',
            'totalRepairs', 'totalRevenue', 'averageRepairTime',
            'repairsByStatus', 'repairsByDay', 'repairsByDevice', 'repairsByBrand',
            'technicianPerformance', 'commonIssues', 'successRate', 'deliveredCount',
            'n1Repairs', 'n1Revenue', 'n1RepGrowth'
        ));
    }

    /**
     * Rapport du stock
     */
    public function stock(Request $request)
    {
        $shopId     = $request->get('shop_id');
        $categoryId = $request->get('category_id');
        $shops      = Shop::active()->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        // Requête de base avec filtre boutique + catégorie
        $baseQuery = Product::withoutGlobalScope('shop');
        if ($shopId) {
            $baseQuery->where('shop_id', $shopId);
        }
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }

        // Valeur totale du stock
        $totalStockValue = (clone $baseQuery)->sum(DB::raw('quantity_in_stock * purchase_price'));
        $totalSellingValue = (clone $baseQuery)->sum(DB::raw('quantity_in_stock * normal_price'));
        $potentialProfit = $totalSellingValue - $totalStockValue;

        // Produits en stock
        $totalProducts = (clone $baseQuery)->count();
        $activeProducts = (clone $baseQuery)->where('is_active', true)->count();
        $outOfStock = (clone $baseQuery)->where('quantity_in_stock', 0)->count();
        $lowStock = (clone $baseQuery)->whereRaw('quantity_in_stock <= stock_alert_threshold')->where('quantity_in_stock', '>', 0)->count();

        // Stock par catégorie
        $categoryQuery = Product::withoutGlobalScope('shop')->with('category');
        if ($shopId)     $categoryQuery->where('shop_id', $shopId);
        if ($categoryId) $categoryQuery->where('category_id', $categoryId);
        $stockByCategory = $categoryQuery
            ->select(
                'category_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(quantity_in_stock) as total_qty'),
                DB::raw('SUM(quantity_in_stock * purchase_price) as total_value')
            )
            ->groupBy('category_id')
            ->get();

        // Produits à commander (stock faible)
        $orderQuery = Product::withoutGlobalScope('shop')->with('category');
        if ($shopId)     $orderQuery->where('shop_id', $shopId);
        if ($categoryId) $orderQuery->where('category_id', $categoryId);
        $productsToOrder = $orderQuery
            ->whereRaw('quantity_in_stock <= stock_alert_threshold')
            ->orderBy('quantity_in_stock')
            ->get();

        // Produits les plus rentables
        $profitQuery = Product::withoutGlobalScope('shop')->where('quantity_in_stock', '>', 0);
        if ($shopId)     $profitQuery->where('shop_id', $shopId);
        if ($categoryId) $profitQuery->where('category_id', $categoryId);
        $mostProfitable = $profitQuery
            ->selectRaw('*, (normal_price - purchase_price) as profit_margin, ((normal_price - purchase_price) / purchase_price * 100) as profit_percentage')
            ->orderByDesc('profit_margin')
            ->limit(10)
            ->get();

        // Rotation du stock (derniers 30 jours)
        $stockRotation = SaleItem::where('created_at', '>=', Carbon::now()->subDays(30))
            ->with('product')
            ->when($categoryId, fn($q) => $q->whereHas('product', fn($sq) => $sq->where('category_id', $categoryId)))
            ->select('product_id', DB::raw('SUM(quantity) as sold_qty'))
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit(20)
            ->get();

        // Produits sans mouvement (30 jours)
        $dormantQuery = Product::withoutGlobalScope('shop')
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0);
        if ($shopId)     $dormantQuery->where('shop_id', $shopId);
        if ($categoryId) $dormantQuery->where('category_id', $categoryId);
        $dormantProducts = $dormantQuery
            ->whereDoesntHave('saleItems', function($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->get();

        // Mouvements de stock récents
        $movementsQuery = StockMovement::withoutGlobalScope('shop')->with(['product', 'user']);
        if ($shopId) {
            $movementsQuery->where('shop_id', $shopId);
        }
        $recentMovements = $movementsQuery
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.reports.stock', compact(
            'totalStockValue', 'totalSellingValue', 'potentialProfit',
            'totalProducts', 'activeProducts', 'outOfStock', 'lowStock',
            'stockByCategory', 'productsToOrder', 'mostProfitable',
            'stockRotation', 'dormantProducts', 'recentMovements',
            'shops', 'shopId', 'categories', 'categoryId'
        ));
    }

    /**
     * Rapport financier / Caisse
     */
    public function financial(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId = $request->get('shop_id');
        $shops = Shop::active()->orderBy('name')->get();

        // Revenus totaux avec filtre boutique
        $salesQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $salesQuery->where('shop_id', $shopId);
        }
        $salesRevenue = $salesQuery->sum('amount_paid');
        
        $repairsQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $repairsQuery->where('shop_id', $shopId);
        }
        // CA Réparations = main d'œuvre uniquement (les pièces sont des ventes)
        $repairsRevenue = $repairsQuery->sum('labor_cost');
        // Pièces de rechange = ventes (ajout au CA ventes)
        $repairsPartsRevenue = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('parts_cost');
        $salesRevenue += $repairsPartsRevenue;
        
        // S.A.V - Pertes (remboursements et différences d'échange négatives)
        $savRefundsQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed']);
        if ($shopId) {
            $savRefundsQuery->where('shop_id', $shopId);
        }
        $savRefunds = (clone $savRefundsQuery)->sum('refund_amount');
        $savExchangeLosses = (clone $savRefundsQuery)->where('exchange_difference', '<', 0)->sum('exchange_difference');
        $savExchangeGains = (clone $savRefundsQuery)->where('exchange_difference', '>', 0)->sum('exchange_difference');
        
        $savTotalImpact = $savRefunds + abs($savExchangeLosses) - $savExchangeGains;
        $totalRevenue = $salesRevenue + $repairsRevenue + $savExchangeGains;
        $netRevenue = $totalRevenue - $savRefunds - abs($savExchangeLosses);

        // Statistiques S.A.V pour le rapport
        $savBaseQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $savBaseQuery->where('shop_id', $shopId);
        }
        $savStats = [
            'total_tickets' => (clone $savBaseQuery)->count(),
            'refunds_count' => (clone $savBaseQuery)->where('type', 'refund')->whereIn('status', ['resolved', 'closed'])->count(),
            'exchanges_count' => (clone $savBaseQuery)->where('type', 'exchange')->whereIn('status', ['resolved', 'closed'])->count(),
            'returns_count' => (clone $savBaseQuery)->where('type', 'return')->whereIn('status', ['resolved', 'closed'])->count(),
            'total_refunds' => $savRefunds,
            'exchange_losses' => abs($savExchangeLosses),
            'exchange_gains' => $savExchangeGains,
            'net_impact' => $savTotalImpact,
        ];

        // Créances
        $creditQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('payment_status', 'credit');
        if ($shopId) {
            $creditQuery->where('shop_id', $shopId);
        }
        $salesCredit = $creditQuery->sum(DB::raw('total_amount - amount_paid'));
        
        $resellerDebtQuery = Reseller::withoutGlobalScope('shop');
        if ($shopId) {
            $resellerDebtQuery->where('shop_id', $shopId);
        }
        $resellerDebt = $resellerDebtQuery->sum('current_debt');

        // Transactions de caisse
        $cashInQuery = CashTransaction::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('amount', '>', 0);
        $cashOutQuery = CashTransaction::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('amount', '<', 0);
        // Note: CashTransaction est lié à CashRegister qui a shop_id
        if ($shopId) {
            $cashInQuery->whereHas('cashRegister', fn($q) => $q->where('shop_id', $shopId));
            $cashOutQuery->whereHas('cashRegister', fn($q) => $q->where('shop_id', $shopId));
        }
        $cashIn = $cashInQuery->sum('amount');
        $cashOut = $cashOutQuery->sum('amount');

        // Revenus par jour (incluant S.A.V)
        $revenueByDayQuery = DB::table('sales')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $revenueByDayQuery->where('shop_id', $shopId);
        }
        $revenueByDay = $revenueByDayQuery
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount_paid) as sales')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $repairsByDayQuery = DB::table('repairs')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNull('deleted_at');
        if ($shopId) {
            $repairsByDayQuery->where('shop_id', $shopId);
        }
        // CA Réparations = main d'œuvre uniquement
        $repairsByDay = $repairsByDayQuery
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(labor_cost) as repairs')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $savByDayQuery = DB::table('sav_tickets')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed'])
            ->whereNull('deleted_at');
        if ($shopId) {
            $savByDayQuery->where('shop_id', $shopId);
        }
        $savByDay = $savByDayQuery
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as refunds'),
                DB::raw('SUM(CASE WHEN exchange_difference < 0 THEN ABS(exchange_difference) ELSE 0 END) as exchange_losses')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Combiner les données par jour
        $dates = collect();
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $savData = $savByDay->get($dateStr);
            $dates->push([
                'date' => $dateStr,
                'sales' => $revenueByDay->get($dateStr)->sales ?? 0,
                'repairs' => $repairsByDay->get($dateStr)->repairs ?? 0,
                'sav_losses' => $savData ? ($savData->refunds + $savData->exchange_losses) : 0,
            ]);
            $currentDate->addDay();
        }

        // Revenus par mode de paiement
        $paymentQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $paymentQuery->where('shop_id', $shopId);
        }
        $revenueByPayment = $paymentQuery
            ->select('payment_method', DB::raw('SUM(amount_paid) as total'))
            ->groupBy('payment_method')
            ->get();

        // Historique des caisses
        $cashRegistersQuery = CashRegister::withoutGlobalScope('shop')
            ->whereBetween('opened_at', [$startDate, $endDate . ' 23:59:59'])
            ->with(['user', 'transactions']);
        if ($shopId) {
            $cashRegistersQuery->where('shop_id', $shopId);
        }
        $cashRegisters = $cashRegistersQuery->orderByDesc('opened_at')->get();

        // Marge bénéficiaire estimée
        $costQuery = SaleItem::whereHas('sale', function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')
                  ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            })
            ->join('products', 'sale_items.product_id', '=', 'products.id');
        $costOfGoodsSold = $costQuery->sum(DB::raw('sale_items.quantity * products.purchase_price'));
        
        $grossProfit = $salesRevenue - $costOfGoodsSold;
        $profitMargin = $salesRevenue > 0 ? round(($grossProfit / $salesRevenue) * 100, 1) : 0;
        
        // Profit net après S.A.V
        $netProfit = $grossProfit + $repairsRevenue - $savTotalImpact;

        // ==================== DÉPENSES ====================
        $expensesQuery = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved');
        if ($shopId) {
            $expensesQuery->where('shop_id', $shopId);
        }
        $totalExpenses = (clone $expensesQuery)->sum('amount');
        $expensesByCategory = (clone $expensesQuery)
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();
        $expensesByPaymentMethod = (clone $expensesQuery)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Dépenses par jour
        $expensesByDayQuery = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved');
        if ($shopId) {
            $expensesByDayQuery->where('shop_id', $shopId);
        }
        $expensesByDay = $expensesByDayQuery
            ->select(
                DB::raw('DATE(expense_date) as date'),
                DB::raw('SUM(amount) as expenses')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Mettre à jour les données par jour avec les dépenses
        $dates = $dates->map(function ($day) use ($expensesByDay) {
            $day['expenses'] = $expensesByDay->get($day['date'])->expenses ?? 0;
            $day['net'] = $day['sales'] + $day['repairs'] - $day['sav_losses'] - $day['expenses'];
            return $day;
        });

        // Bénéfice net final (après dépenses)
        $finalNetProfit = $netProfit - $totalExpenses;

        return view('admin.reports.financial', compact(
            'startDate', 'endDate', 'shops', 'shopId',
            'salesRevenue', 'repairsRevenue', 'totalRevenue', 'netRevenue',
            'savStats', 'savTotalImpact',
            'salesCredit', 'resellerDebt',
            'cashIn', 'cashOut',
            'dates', 'revenueByPayment', 'cashRegisters',
            'costOfGoodsSold', 'grossProfit', 'profitMargin', 'netProfit',
            'totalExpenses', 'expensesByCategory', 'expensesByPaymentMethod', 'finalNetProfit'
        ));
    }

    /**
     * Rapport S.A.V détaillé - Analyse complète avec indicateurs anti-malversation
     */
    public function sav(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id');
        $savType      = $request->get('sav_type');      // return|exchange|refund|warranty|complaint|other
        $customerType = $request->get('customer_type'); // customer|reseller
        $shops        = Shop::active()->orderBy('name')->get();

        // Statistiques globales avec filtre boutique + type SAV + type client
        $baseQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId)       $baseQuery->where('shop_id', $shopId);
        if ($savType)      $baseQuery->where('type', $savType);
        if ($customerType === 'reseller') {
            $baseQuery->whereHas('sale', fn($q) => $q->where('client_type', 'reseller'));
        } elseif ($customerType === 'customer') {
            $baseQuery->where(function($q) {
                $q->whereNull('sale_id')
                  ->orWhereHas('sale', fn($sq) => $sq->whereIn('client_type', ['customer', 'walk-in']));
            });
        }
        $totalTickets = (clone $baseQuery)->count();
        $openTickets = (clone $baseQuery)->open()->count();
        $closedTickets = (clone $baseQuery)->closed()->count();

        // Impact financier total
        $financeQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed']);
        if ($shopId) {
            $financeQuery->where('shop_id', $shopId);
        }
        $totalRefunds = (clone $financeQuery)->sum('refund_amount');
        $totalExchangeLosses = (clone $financeQuery)->where('exchange_difference', '<', 0)
            ->sum(DB::raw('ABS(exchange_difference)'));
        $totalExchangeGains = (clone $financeQuery)->where('exchange_difference', '>', 0)
            ->sum('exchange_difference');

        // Tickets par type
        $typeQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $typeQuery->where('shop_id', $shopId);
        }
        $ticketsByType = $typeQuery
            ->select('type', 
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds'),
                DB::raw('SUM(CASE WHEN exchange_difference < 0 THEN ABS(exchange_difference) ELSE 0 END) as exchange_losses')
            )
            ->groupBy('type')
            ->get();

        // Tickets par statut
        $statusQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $statusQuery->where('shop_id', $shopId);
        }
        $ticketsByStatus = $statusQuery
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Tickets par priorité
        $priorityQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $priorityQuery->where('shop_id', $shopId);
        }
        $ticketsByPriority = $priorityQuery
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();

        // Évolution par jour
        $dayQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $dayQuery->where('shop_id', $shopId);
        }
        $ticketsByDay = $dayQuery
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as refunds')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ========== INDICATEURS ANTI-MALVERSATION ==========

        // 1. Employés avec le plus de tickets S.A.V (création)
        $creatorQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $creatorQuery->where('shop_id', $shopId);
        }
        $ticketsByCreator = $creatorQuery
            ->with('creator')
            ->select('created_by',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(CASE WHEN type = "refund" THEN 1 ELSE 0 END) as refund_count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds'),
                DB::raw('SUM(CASE WHEN exchange_difference < 0 THEN ABS(exchange_difference) ELSE 0 END) as exchange_losses')
            )
            ->groupBy('created_by')
            ->orderByDesc('total_refunds')
            ->get();

        // 2. Ventes avec le plus de tickets S.A.V (ventes problématiques)
        $salesSavQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('sav_tickets.created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('sale_id');
        if ($shopId) {
            $salesSavQuery->where('shop_id', $shopId);
        }
        $salesWithMostSav = $salesSavQuery
            ->with(['sale.user', 'sale.customer', 'sale.reseller'])
            ->select('sale_id',
                DB::raw('COUNT(*) as sav_count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds')
            )
            ->groupBy('sale_id')
            ->having('sav_count', '>=', 1)
            ->orderByDesc('sav_count')
            ->limit(20)
            ->get();

        // 3. Produits les plus retournés/échangés
        $productsQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('product_id');
        if ($shopId) {
            $productsQuery->where('shop_id', $shopId);
        }
        $problematicProducts = $productsQuery
            ->with('product')
            ->select('product_id',
                DB::raw('COUNT(*) as sav_count'),
                DB::raw('SUM(CASE WHEN type = "return" THEN 1 ELSE 0 END) as return_count'),
                DB::raw('SUM(CASE WHEN type = "exchange" THEN 1 ELSE 0 END) as exchange_count'),
                DB::raw('SUM(CASE WHEN type = "refund" THEN 1 ELSE 0 END) as refund_count'),
                DB::raw('SUM(CASE WHEN type = "warranty" THEN 1 ELSE 0 END) as warranty_count')
            )
            ->groupBy('product_id')
            ->orderByDesc('sav_count')
            ->limit(15)
            ->get();

        // 4. Clients avec le plus de réclamations (clients à risque ou abus)
        $customersQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('customer_id');
        if ($shopId) {
            $customersQuery->where('shop_id', $shopId);
        }
        $customersWithMostSav = $customersQuery
            ->with('customer')
            ->select('customer_id',
                DB::raw('COUNT(*) as sav_count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds')
            )
            ->groupBy('customer_id')
            ->having('sav_count', '>=', 2)
            ->orderByDesc('sav_count')
            ->limit(15)
            ->get();

        // 5. Alertes - Remboursements suspects
        // (montants élevés, remboursements rapides après vente, même employé vente/SAV)
        $suspiciousQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('sav_tickets.created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $suspiciousQuery->where('shop_id', $shopId);
        }
        $suspiciousRefunds = $suspiciousQuery
            ->where('type', 'refund')
            ->whereIn('status', ['resolved', 'closed'])
            ->where('refund_amount', '>', 0)
            ->with(['sale', 'creator', 'customer'])
            ->get()
            ->filter(function($ticket) {
                $alerts = [];
                
                // Alerte 1: Remboursement élevé (> 50000 FCFA)
                if ($ticket->refund_amount > 50000) {
                    $alerts[] = 'montant_eleve';
                }
                
                // Alerte 2: Même personne a créé la vente et le ticket SAV
                if ($ticket->sale && $ticket->sale->user_id === $ticket->created_by) {
                    $alerts[] = 'meme_employe';
                }
                
                // Alerte 3: Ticket créé très rapidement après la vente (< 24h)
                if ($ticket->sale && $ticket->created_at->diffInHours($ticket->sale->created_at) < 24) {
                    $alerts[] = 'rapide';
                }
                
                // Alerte 4: Pas de vente liée (remboursement sans trace de vente)
                if (!$ticket->sale_id) {
                    $alerts[] = 'sans_vente';
                }
                
                $ticket->alerts = $alerts;
                return count($alerts) > 0;
            })
            ->values();

        // 6. Taux de SAV par vendeur (pour identifier les vendeurs problématiques)
        $salesCountQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $salesCountQuery->where('shop_id', $shopId);
        }
        $salesCount = $salesCountQuery
            ->select('user_id', DB::raw('COUNT(*) as sales_count'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $vendorQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('sav_tickets.created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('sale_id');
        if ($shopId) {
            $vendorQuery->where('sav_tickets.shop_id', $shopId);
        }
        $savByVendor = $vendorQuery
            ->join('sales', 'sav_tickets.sale_id', '=', 'sales.id')
            ->with('sale.user')
            ->select('sales.user_id',
                DB::raw('COUNT(DISTINCT sav_tickets.id) as sav_count')
            )
            ->groupBy('sales.user_id')
            ->get()
            ->map(function($item) use ($salesCount) {
                $totalSales = $salesCount->get($item->user_id)->sales_count ?? 0;
                $item->total_sales = $totalSales;
                $item->sav_rate = $totalSales > 0 ? round(($item->sav_count / $totalSales) * 100, 2) : 0;
                return $item;
            })
            ->sortByDesc('sav_rate')
            ->values();

        // 7. Temps moyen de résolution
        $avgTimeQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereNotNull('resolved_at');
        if ($shopId) {
            $avgTimeQuery->where('shop_id', $shopId);
        }
        $avgResolutionTime = $avgTimeQuery
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        // 8. Tickets en attente depuis longtemps (> 7 jours)
        $oldQuery = SavTicket::withoutGlobalScope('shop')
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->open();
        if ($shopId) {
            $oldQuery->where('shop_id', $shopId);
        }
        $oldOpenTickets = $oldQuery
            ->with(['customer', 'creator', 'assignedUser'])
            ->orderBy('created_at')
            ->get();

        // 9. Détail des derniers remboursements effectués (pour audit)
        $recentQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereIn('type', ['refund', 'return'])
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function($q) {
                $q->where('refund_amount', '>', 0)
                  ->orWhere('exchange_difference', '<', 0);
            });
        if ($shopId) {
            $recentQuery->where('shop_id', $shopId);
        }
        $recentRefunds = $recentQuery
            ->with(['customer', 'sale', 'product', 'creator', 'assignedUser'])
            ->orderByDesc('resolved_at')
            ->limit(30)
            ->get();

        // Comparaison avec période précédente
        $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $previousStart = Carbon::parse($startDate)->subDays($daysDiff)->format('Y-m-d');
        $previousEnd = Carbon::parse($startDate)->subDay()->format('Y-m-d');
        
        $prevQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$previousStart, $previousEnd . ' 23:59:59']);
        if ($shopId) {
            $prevQuery->where('shop_id', $shopId);
        }
        $previousTotalTickets = (clone $prevQuery)->count();
        $previousRefunds = (clone $prevQuery)->sum('refund_amount');

        $ticketGrowth = $previousTotalTickets > 0 
            ? round((($totalTickets - $previousTotalTickets) / $previousTotalTickets) * 100, 1) 
            : 0;
        $refundGrowth = $previousRefunds > 0 
            ? round((($totalRefunds - $previousRefunds) / $previousRefunds) * 100, 1) 
            : 0;

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

    /**
     * Rapport des clients
     */
    public function customers(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id');
        $customerType = $request->get('customer_type'); // 'customer' | 'reseller' | null
        $shops        = Shop::active()->orderBy('name')->get();

        // Statistiques globales avec filtre boutique
        $customerQuery = Customer::withoutGlobalScope('shop');
        if ($shopId) {
            $customerQuery->where('shop_id', $shopId);
        }
        $totalCustomers = (clone $customerQuery)->count();
        $newCustomers = (clone $customerQuery)->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])->count();
        $activeCustomers = (clone $customerQuery)->whereHas('sales', function($q) use ($startDate, $endDate, $shopId) {
            $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
            if ($shopId) {
                $q->where('shop_id', $shopId);
            }
        })->count();

        // Top clients par CA
        $topQuery = Customer::withoutGlobalScope('shop');
        if ($shopId) {
            $topQuery->where('shop_id', $shopId);
        }
        $topCustomersByRevenue = $topQuery->withSum(['sales' => function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            }], 'total_amount')
            ->withCount(['sales' => function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            }])
            ->orderByDesc('sales_sum_total_amount')
            ->limit(20)
            ->get();

        // Clients fidèles (plus de 3 achats)
        $loyalQuery = Customer::withoutGlobalScope('shop');
        if ($shopId) {
            $loyalQuery->where('shop_id', $shopId);
        }
        $loyalCustomers = $loyalQuery->withCount(['sales' => function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            }])
            ->having('sales_count', '>=', 3)
            ->orderByDesc('sales_count')
            ->get();

        // Clients avec réparations
        $repairsQuery = Customer::withoutGlobalScope('shop');
        if ($shopId) {
            $repairsQuery->where('shop_id', $shopId);
        }
        $customersWithRepairs = $repairsQuery->withCount(['repairs' => function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            }])
            ->having('repairs_count', '>', 0)
            ->orderByDesc('repairs_count')
            ->limit(20)
            ->get();

        // Acquisition clients par jour
        $dayQuery = Customer::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) {
            $dayQuery->where('shop_id', $shopId);
        }
        $customersByDay = $dayQuery
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revendeurs
        $resellerQuery = Reseller::withoutGlobalScope('shop');
        if ($shopId) {
            $resellerQuery->where('shop_id', $shopId);
        }
        $totalResellers = (clone $resellerQuery)->count();
        $resellersWithDebt = (clone $resellerQuery)->where('current_debt', '>', 0)->count();
        $totalResellerDebt = (clone $resellerQuery)->sum('current_debt');

        // Top revendeurs
        $topResellerQuery = Reseller::withoutGlobalScope('shop');
        if ($shopId) {
            $topResellerQuery->where('shop_id', $shopId);
        }
        $topResellers = $topResellerQuery->withSum(['sales' => function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            }], 'total_amount')
            ->withCount(['sales' => function($q) use ($startDate, $endDate, $shopId) {
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                if ($shopId) {
                    $q->where('shop_id', $shopId);
                }
            }])
            ->orderByDesc('sales_sum_total_amount')
            ->limit(10)
            ->get();

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

    /** PDF Rapport des ventes */
    public function salesPdf(Request $request)
    {
        $startDate  = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId     = $request->get('shop_id');
        $shop       = $shopId ? Shop::find($shopId) : null;

        $q = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $totalSales   = (clone $q)->count();
        $totalRevenue = (clone $q)->sum('total_amount');
        $totalPaid    = (clone $q)->sum('amount_paid');
        $totalCredit  = (clone $q)->where('payment_status', 'credit')->sum('total_amount');

        $salesByPayment = (clone $q)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')->get();

        $salesByClientType = (clone $q)
            ->select('client_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('client_type')->get();

        $topProducts = SaleItem::whereHas('sale', fn($sq) =>
                $sq->withoutGlobalScope('shop')
                   ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                   ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            )
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_revenue'))
            ->groupBy('product_id')->orderByDesc('total_revenue')->limit(15)->get();

        $salesByDay = (clone $q)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')->orderBy('date')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.sales-pdf', compact(
            'startDate', 'endDate', 'shop',
            'totalSales', 'totalRevenue', 'totalPaid', 'totalCredit',
            'salesByPayment', 'salesByClientType', 'topProducts', 'salesByDay'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_ventes_' . $startDate . '_' . $endDate . '.pdf');
    }

    /** PDF Rapport des réparations */
    public function repairsPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id');
        $shop      = $shopId ? Shop::find($shopId) : null;

        $q = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $totalRepairs   = (clone $q)->count();
        $totalRevenue   = (clone $q)->sum('labor_cost');
        $deliveredCount = (clone $q)->where('status', 'delivered')->count();
        $successRate    = $totalRepairs > 0 ? round(($deliveredCount / $totalRepairs) * 100, 1) : 0;
        $avgRepairTime  = (clone $q)->whereNotNull('repaired_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, repaired_at)) as avg_hours')
            ->value('avg_hours');

        $repairsByStatus = (clone $q)
            ->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->get();

        $repairsByDevice = (clone $q)
            ->select('device_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(labor_cost) as total'))
            ->groupBy('device_type')->orderByDesc('count')->get();

        $repairsByBrand = (clone $q)->whereNotNull('device_brand')
            ->select('device_brand', DB::raw('COUNT(*) as count'))
            ->groupBy('device_brand')->orderByDesc('count')->limit(10)->get();

        $techPerformance = (clone $q)->whereNotNull('technician_id')
            ->with('technician')
            ->select('technician_id',
                DB::raw('COUNT(*) as total_repairs'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(labor_cost) as total_revenue'))
            ->groupBy('technician_id')->get();

        $repairsByDay = (clone $q)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(labor_cost) as total'))
            ->groupBy('date')->orderBy('date')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.repairs-pdf', compact(
            'startDate', 'endDate', 'shop',
            'totalRepairs', 'totalRevenue', 'deliveredCount', 'successRate', 'avgRepairTime',
            'repairsByStatus', 'repairsByDevice', 'repairsByBrand', 'techPerformance', 'repairsByDay'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_reparations_' . $startDate . '_' . $endDate . '.pdf');
    }

    /** PDF Rapport du stock */
    public function stockPdf(Request $request)
    {
        $shopId     = $request->get('shop_id');
        $categoryId = $request->get('category_id');
        $shop       = $shopId ? Shop::find($shopId) : null;
        $category   = $categoryId ? Category::find($categoryId) : null;

        $base = Product::withoutGlobalScope('shop');
        if ($shopId)     $base->where('shop_id', $shopId);
        if ($categoryId) $base->where('category_id', $categoryId);

        $totalStockValue   = (clone $base)->sum(DB::raw('quantity_in_stock * purchase_price'));
        $totalSellingValue = (clone $base)->sum(DB::raw('quantity_in_stock * normal_price'));
        $totalProducts     = (clone $base)->count();
        $outOfStock        = (clone $base)->where('quantity_in_stock', 0)->count();
        $lowStock          = (clone $base)->whereRaw('quantity_in_stock <= stock_alert_threshold')->where('quantity_in_stock', '>', 0)->count();

        $stockByCategory = (clone $base)->with('category')
            ->select('category_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(quantity_in_stock) as total_qty'),
                DB::raw('SUM(quantity_in_stock * purchase_price) as total_value'))
            ->groupBy('category_id')->get();

        $productsToOrder = (clone $base)->with('category')
            ->whereRaw('quantity_in_stock <= stock_alert_threshold')
            ->orderBy('quantity_in_stock')->get();

        $mostProfitable = (clone $base)->where('quantity_in_stock', '>', 0)
            ->selectRaw('*, (normal_price - purchase_price) as profit_margin')
            ->orderByDesc('profit_margin')->limit(10)->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.stock-pdf', compact(
            'shop', 'category',
            'totalStockValue', 'totalSellingValue', 'totalProducts', 'outOfStock', 'lowStock',
            'stockByCategory', 'productsToOrder', 'mostProfitable'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_stock_' . date('Y-m-d') . '.pdf');
    }

    /** PDF Rapport financier */
    public function financialPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id');
        $shop      = $shopId ? Shop::find($shopId) : null;

        $salesRevenue = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('amount_paid');

        $repairsRevenue = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('labor_cost');
        // Pièces de rechange = ventes (ajout au CA ventes)
        $repairsPartsRevenue = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('parts_cost');
        $salesRevenue += $repairsPartsRevenue;

        $savQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId));

        $savRefunds        = (clone $savQuery)->sum('refund_amount');
        $savExchangeLosses = (clone $savQuery)->where('exchange_difference', '<', 0)->sum(DB::raw('ABS(exchange_difference)'));
        $savExchangeGains  = (clone $savQuery)->where('exchange_difference', '>', 0)->sum('exchange_difference');
        $netRevenue        = $salesRevenue + $repairsRevenue + $savExchangeGains - $savRefunds - $savExchangeLosses;

        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->sum('amount');

        $expensesByCategory = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')->with('category')->get();

        $revenueByPayment = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select('payment_method', DB::raw('SUM(amount_paid) as total'))
            ->groupBy('payment_method')->get();

        $costOfGoodsSold = SaleItem::whereHas('sale', fn($q) =>
            $q->withoutGlobalScope('shop')
              ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
              ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
        )->join('products', 'sale_items.product_id', '=', 'products.id')
         ->sum(DB::raw('sale_items.quantity * products.purchase_price'));

        $grossProfit   = $salesRevenue - $costOfGoodsSold;
        $profitMargin  = $salesRevenue > 0 ? round(($grossProfit / $salesRevenue) * 100, 1) : 0;
        $finalNetProfit = $grossProfit + $repairsRevenue - $savRefunds - $savExchangeLosses + $savExchangeGains - $totalExpenses;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.financial-pdf', compact(
            'startDate', 'endDate', 'shop',
            'salesRevenue', 'repairsRevenue', 'netRevenue',
            'savRefunds', 'savExchangeLosses', 'savExchangeGains',
            'totalExpenses', 'expensesByCategory', 'revenueByPayment',
            'costOfGoodsSold', 'grossProfit', 'profitMargin', 'finalNetProfit'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_financier_' . $startDate . '_' . $endDate . '.pdf');
    }

    /** PDF Rapport clients */
    public function customersPdf(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id');
        $customerType = $request->get('customer_type');
        $shop         = $shopId ? Shop::find($shopId) : null;

        // Top customers
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
            ->orderByDesc('sales_sum_total_amount')
            ->limit(20)->get();

        // Top resellers
        $topResellers = Reseller::withoutGlobalScope('shop')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->withSum(['sales' => fn($q) =>
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                  ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ], 'total_amount')
            ->withCount(['sales' => fn($q) =>
                $q->withoutGlobalScope('shop')->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                  ->when($shopId, fn($r) => $r->where('shop_id', $shopId))
            ])
            ->orderByDesc('sales_sum_total_amount')
            ->limit(10)->get();

        $totalCustomers  = Customer::withoutGlobalScope('shop')->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count();
        $totalResellers  = Reseller::withoutGlobalScope('shop')->when($shopId, fn($q) => $q->where('shop_id', $shopId))->count();
        $totalResellerDebt = Reseller::withoutGlobalScope('shop')->when($shopId, fn($q) => $q->where('shop_id', $shopId))->sum('current_debt');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.customers-pdf', compact(
            'startDate', 'endDate', 'shop', 'customerType',
            'totalCustomers', 'totalResellers', 'totalResellerDebt',
            'topCustomersByRevenue', 'topResellers'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_clients_' . $startDate . '_' . $endDate . '.pdf');
    }

    /** PDF Rapport S.A.V */
    public function savPdf(Request $request)
    {
        $startDate    = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate      = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId       = $request->get('shop_id');
        $savType      = $request->get('sav_type');
        $customerType = $request->get('customer_type');
        $shop         = $shopId ? Shop::find($shopId) : null;

        $base = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        if ($shopId)       $base->where('shop_id', $shopId);
        if ($savType)      $base->where('type', $savType);
        if ($customerType === 'reseller') {
            $base->whereHas('sale', fn($q) => $q->where('client_type', 'reseller'));
        } elseif ($customerType === 'customer') {
            $base->where(fn($q) => $q->whereNull('sale_id')->orWhereHas('sale', fn($sq) => $sq->whereIn('client_type', ['customer', 'walk-in'])));
        }

        $totalTickets = (clone $base)->count();
        $openTickets  = (clone $base)->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'waiting_parts'])->count();

        $financeBase = (clone $base)->whereIn('status', ['resolved', 'closed']);
        $totalRefunds       = (clone $financeBase)->sum('refund_amount');
        $totalExchangeLosses = (clone $financeBase)->where('exchange_difference', '<', 0)->sum(DB::raw('ABS(exchange_difference)'));

        $ticketsByType = (clone $base)
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(refund_amount,0)) as total_refunds'))
            ->groupBy('type')->get();

        $ticketsByStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->get();

        $recentRefunds = (clone $base)
            ->whereIn('type', ['refund', 'return'])
            ->whereIn('status', ['resolved', 'closed'])
            ->where(fn($q) => $q->where('refund_amount', '>', 0)->orWhere('exchange_difference', '<', 0))
            ->with(['customer', 'sale', 'product', 'creator'])
            ->orderByDesc('resolved_at')->limit(30)->get();

        $ticketsByCreator = (clone $base)
            ->with('creator')
            ->select('created_by',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(COALESCE(refund_amount,0)) as total_refunds'))
            ->groupBy('created_by')->orderByDesc('total_refunds')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.sav-pdf', compact(
            'startDate', 'endDate', 'shop', 'savType', 'customerType',
            'totalTickets', 'openTickets', 'totalRefunds', 'totalExchangeLosses',
            'ticketsByType', 'ticketsByStatus', 'recentRefunds', 'ticketsByCreator'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('rapport_sav_' . $startDate . '_' . $endDate . '.pdf');
    }

    /**
     * Export PDF — cumul des ventes par produit avec marges
     */
    public function salesProductsPdf(Request $request)
    {
        $startDate  = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId     = $request->get('shop_id');
        $customerId = $request->get('customer_id');
        $categoryId = $request->get('category_id');
        $productId  = $request->get('product_id');

        $shop = $shopId ? Shop::find($shopId) : null;

        // Tous les articles vendus sur la période, groupés par produit
        $itemsQuery = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereNull('sales.deleted_at')
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
                DB::raw('SUM(sale_items.total_price) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * products.purchase_price) as total_cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($row) {
                $row->margin      = $row->total_revenue - $row->total_cost;
                $row->margin_pct  = $row->total_revenue > 0
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.sales-products-pdf', compact(
            'rows', 'totals', 'startDate', 'endDate', 'shop'
        ))->setPaper('a4', 'landscape');

        $filename = 'ventes_produits_' . $startDate . '_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export des données en CSV
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'sales');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        switch ($type) {
            case 'sales':
                return $this->exportSales($startDate, $endDate);
            case 'repairs':
                return $this->exportRepairs($startDate, $endDate);
            case 'stock':
                return $this->exportStock();
            case 'sav':
                return $this->exportSav($startDate, $endDate);
            default:
                return back()->with('error', 'Type d\'export invalide.');
        }
    }

    protected function exportSales($startDate, $endDate)
    {
        $filename = 'ventes_' . $startDate . '_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['N° Facture', 'Date', 'Client', 'Type', 'Produits', 'Montant', 'Payé', 'Mode', 'Caissier']);

            // lazyById() streame par chunks — évite de charger tout en RAM
            Sale::with(['customer', 'reseller', 'user', 'items.product'])
                ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->lazyById()
                ->each(function ($sale) use ($file) {
                    $client = $sale->client_type === 'customer'
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

    protected function exportRepairs($startDate, $endDate)
    {
        $filename = 'reparations_' . $startDate . '_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

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

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['SKU', 'Produit', 'Catégorie', 'Stock', 'Prix Achat', 'Prix Minimum', 'Valeur Stock']);

            Product::with('category')
                ->lazyById()
                ->each(function ($product) use ($file) {
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

    /**
     * Export S.A.V en CSV - Détaillé pour audit anti-malversation
     */
    protected function exportSav($startDate, $endDate)
    {
        $filename = 'sav_audit_' . $startDate . '_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

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
                    $sameEmployee = $ticket->sale && $ticket->sale->user_id === $ticket->created_by ? 'OUI' : 'NON';
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

    /**
     * Relevé de créance d'un client particulier (toutes boutiques)
     */
    /**
     * Relevé de créance d'un revendeur (toutes boutiques, par période)
     */
    public function resellerStatement(Request $request, Reseller $reseller)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id');
        $shops     = Shop::active()->orderBy('name')->get();

        // Ventes à crédit sur la période pour ce revendeur
        $salesQuery = Sale::withoutGlobalScope('shop')
            ->with(['items.product', 'shop', 'user'])
            ->where('reseller_id', $reseller->id)
            ->whereIn('payment_status', ['credit', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        if ($shopId) {
            $salesQuery->where('shop_id', $shopId);
        }

        $sales = $salesQuery->orderBy('created_at')->get();

        // Paiements effectués sur la période
        $paymentsQuery = ResellerPayment::where('reseller_id', $reseller->id)
            ->with(['user', 'sale'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        $payments = $paymentsQuery->orderBy('created_at')->get();

        // Totaux ventes
        $totalAmount      = $sales->sum('total_amount');
        $totalDiscount    = $sales->sum('discount_amount');
        $totalPaid        = $sales->sum('amount_paid');
        $totalOutstanding = $sales->sum('amount_due');

        // Totalité des paiements sur la période
        $totalPaymentsAmount = $payments->sum('amount');

        return view('admin.customers.statement', compact(
            'reseller', 'sales', 'payments',
            'startDate', 'endDate', 'shops', 'shopId',
            'totalAmount', 'totalPaid', 'totalOutstanding', 'totalDiscount',
            'totalPaymentsAmount'
        ));
    }

    /**
     * Export PDF du relevé de créance revendeur
     */
    public function resellerStatementPdf(Request $request, Reseller $reseller)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $shopId    = $request->get('shop_id');

        $salesQuery = Sale::withoutGlobalScope('shop')
            ->with(['items.product', 'shop', 'user'])
            ->where('reseller_id', $reseller->id)
            ->whereIn('payment_status', ['credit', 'partial'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        if ($shopId) $salesQuery->where('shop_id', $shopId);

        $sales = $salesQuery->orderBy('created_at')->get();

        $paymentsQuery = ResellerPayment::where('reseller_id', $reseller->id)
            ->with(['user'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        $payments = $paymentsQuery->orderBy('created_at')->get();

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

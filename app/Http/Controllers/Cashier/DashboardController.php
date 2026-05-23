<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

/**
 * Tableau de bord Caissière - Opérations quotidiennes
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $shopId = $user->shop_id;

        // Vérifier si une caisse est ouverte
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        // Statistiques du jour
        $todaySales = Sale::where('user_id', $user->id)->today()->get();
        $stats = [
            'today_sales_count' => $todaySales->count(),
            'today_sales_amount' => $todaySales->sum('total_amount'),
            'today_cash_sales' => $todaySales->where('payment_method', 'cash')->sum('total_amount'),
            'today_mobile_money_sales' => $todaySales->where('payment_method', 'mobile_money')->sum('total_amount'),
            'today_card_sales' => $todaySales->where('payment_method', 'card')->sum('total_amount'),
            'today_credit_sales' => $todaySales->where('payment_status', 'credit')->sum('total_amount'),
        ];

        // Dépenses du jour
        $todayExpenses = Expense::where('shop_id', $shopId)->today()->approved();
        $stats['today_expenses_count'] = $todayExpenses->count();
        $stats['today_expenses_amount'] = $todayExpenses->sum('amount');
        $stats['today_pending_expenses'] = Expense::where('shop_id', $shopId)->today()->pending()->count();

        // Réparations du jour
        $todayRepairs = Repair::where('created_by', $user->id)->today()->get();
        $stats['today_repairs_count'] = $todayRepairs->count();
        $stats['today_repairs_labor'] = $todayRepairs->sum('labor_cost');
        $stats['today_repairs_parts'] = $todayRepairs->sum('parts_cost');
        $stats['today_repairs_with_parts'] = $todayRepairs->where('parts_cost', '>', 0)->count();
        // Pièces → ventes ; main d'œuvre → réparations
        $stats['today_repairs_amount'] = $stats['today_repairs_labor'];
        $stats['today_sales_amount'] += $stats['today_repairs_parts'];

        // CA total du jour (ventes + pièces réparations + main d'œuvre)
        $stats['today_total_ca'] = $stats['today_sales_amount'] + $stats['today_repairs_amount'];

        // Réparations prêtes pour retrait
        $readyRepairs = Repair::whereIn('status', [
            Repair::STATUS_REPAIRED,
            Repair::STATUS_READY_FOR_PICKUP,
        ])->with('customer')->get();

        // Réparations terminées et livrées (historique récent de la boutique)
        $deliveredRepairs = Repair::whereIn('status', [
            Repair::STATUS_DELIVERED,
            Repair::STATUS_REPAIRED,
            Repair::STATUS_READY_FOR_PICKUP,
        ])->with(['customer', 'technician'])
            ->latest('repaired_at')
            ->take(10)
            ->get();

        // Dernières ventes
        $recentSales = Sale::where('user_id', $user->id)
            ->with(['customer', 'reseller', 'items.product'])
            ->latest()
            ->take(5)
            ->get();

        // Revendeurs avec créances
        $resellersWithDebt = Reseller::withDebt()
            ->orderByDesc('current_debt')
            ->take(5)
            ->get();

        // Produits populaires aujourd'hui
        $popularProducts = Product::withCount(['saleItems as today_sold' => function ($query) {
            $query->whereHas('sale', fn($q) => $q->today());
        }])
            ->having('today_sold', '>', 0)
            ->orderByDesc('today_sold')
            ->take(5)
            ->get();

        // Dernières dépenses
        $recentExpenses = Expense::where('shop_id', $shopId)
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        // Transferts de stock entrants en attente de confirmation
        $pendingTransfers = StockTransfer::with(['fromShop', 'items'])
            ->where('to_shop_id', $shopId)
            ->where('status', 'in_transit')
            ->latest()
            ->get();

        return view('cashier.dashboard', compact(
            'cashRegister',
            'stats',
            'readyRepairs',
            'deliveredRepairs',
            'recentSales',
            'resellersWithDebt',
            'popularProducts',
            'recentExpenses',
            'pendingTransfers'
        ));
    }
}

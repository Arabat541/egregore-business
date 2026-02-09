<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\Sale;
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

        // Bénéfice net du jour (CA - Dépenses)
        $stats['today_net_profit'] = $stats['today_sales_amount'] - $stats['today_expenses_amount'];

        // Réparations du jour
        $todayRepairs = Repair::where('created_by', $user->id)->today()->get();
        $stats['today_repairs_count'] = $todayRepairs->count();
        // CA Réparations = main d'œuvre uniquement (les pièces sont des ventes)
        $stats['today_repairs_amount'] = $todayRepairs->sum('labor_cost');

        // Réparations prêtes pour retrait
        $readyRepairs = Repair::whereIn('status', [
            Repair::STATUS_REPAIRED,
            Repair::STATUS_READY_FOR_PICKUP,
        ])->with('customer')->get();

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

        return view('cashier.dashboard', compact(
            'cashRegister',
            'stats',
            'readyRepairs',
            'recentSales',
            'resellersWithDebt',
            'popularProducts',
            'recentExpenses'
        ));
    }
}

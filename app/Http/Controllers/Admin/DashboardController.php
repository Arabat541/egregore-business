<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SavTicket;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Tableau de bord Admin - Vue d'ensemble en lecture seule
 */
class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'total_customers' => Customer::count(),
            'total_resellers' => Reseller::count(),
            'total_products' => Product::count(),
            'low_stock_products' => Product::lowStock()->count(),
            'out_of_stock_products' => Product::outOfStock()->count(),
        ];

        // Ventes du jour
        $todaySales = Sale::today()->get();
        $stats['today_sales_count'] = $todaySales->count();
        $stats['today_sales_amount'] = $todaySales->sum('total_amount');

        // Ventes du mois
        $monthSales = Sale::thisMonth()->get();
        $stats['month_sales_count'] = $monthSales->count();
        $stats['month_sales_amount'] = $monthSales->sum('total_amount');

        // Dépenses du jour et du mois
        $stats['today_expenses'] = Expense::today()->approved()->sum('amount');
        $stats['month_expenses'] = Expense::currentMonth()->approved()->sum('amount');
        $stats['pending_expenses'] = Expense::pending()->count();

        // Bénéfice net (Ventes - Dépenses)
        $stats['today_profit'] = $stats['today_sales_amount'] - $stats['today_expenses'];
        $stats['month_profit'] = $stats['month_sales_amount'] - $stats['month_expenses'];

        // Réparations
        $stats['pending_repairs'] = Repair::pending()->count();
        $stats['today_repairs'] = Repair::today()->count();

        // Créances revendeurs
        $stats['total_debt'] = Reseller::sum('current_debt');
        $stats['resellers_with_debt'] = Reseller::withDebt()->count();

        // S.A.V Statistiques
        $stats['sav_open_tickets'] = SavTicket::open()->count();
        $stats['sav_urgent_tickets'] = SavTicket::open()->urgent()->count();
        $stats['sav_month_refunds'] = SavTicket::where('created_at', '>=', now()->startOfMonth())
            ->whereIn('status', ['resolved', 'closed'])
            ->sum('refund_amount');
        $stats['sav_month_tickets'] = SavTicket::where('created_at', '>=', now()->startOfMonth())->count();

        // Dernières ventes
        $recentSales = Sale::with(['user', 'customer', 'reseller'])
            ->latest()
            ->take(10)
            ->get();

        // Dernières réparations
        $recentRepairs = Repair::with(['customer', 'technician'])
            ->latest()
            ->take(10)
            ->get();

        // Produits en stock faible
        $lowStockProducts = Product::lowStock()
            ->with('category')
            ->take(10)
            ->get();

        // Tickets S.A.V urgents
        $urgentSavTickets = SavTicket::open()
            ->urgent()
            ->with(['customer', 'creator'])
            ->latest()
            ->take(5)
            ->get();

        // Top catégories de dépenses du mois
        $topExpenseCategories = Expense::currentMonth()
            ->approved()
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->with('category')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Dernières dépenses
        $recentExpenses = Expense::with(['category', 'user'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentSales',
            'recentRepairs',
            'lowStockProducts',
            'urgentSavTickets',
            'topExpenseCategories',
            'recentExpenses'
        ));
    }
}

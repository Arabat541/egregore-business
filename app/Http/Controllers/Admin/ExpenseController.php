<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Afficher la liste des dépenses (toutes les boutiques pour super admin, ou sa boutique)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Expense::with(['category', 'user', 'shop', 'approver'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filtre par boutique
        if ($user->shop_id) {
            $query->where('shop_id', $user->shop_id);
            $shops = collect();
        } else {
            $shops = Shop::orderBy('name')->get();
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
        }

        // Autres filtres
        if ($request->filled('category')) {
            $query->where('expense_category_id', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('beneficiary', 'like', "%{$search}%");
            });
        }

        $expenses = $query->paginate(25)->withQueryString();

        // Statistiques
        $statsQuery = Expense::query();
        if ($user->shop_id) {
            $statsQuery->where('shop_id', $user->shop_id);
        } elseif ($request->filled('shop_id')) {
            $statsQuery->where('shop_id', $request->shop_id);
        }

        $todayTotal = (clone $statsQuery)->whereDate('expense_date', today())->approved()->sum('amount');
        $monthTotal = (clone $statsQuery)->currentMonth()->approved()->sum('amount');
        $pendingCount = (clone $statsQuery)->pending()->count();
        $pendingTotal = (clone $statsQuery)->pending()->sum('amount');

        // Catégories pour filtre
        $categoriesQuery = ExpenseCategory::query();
        if ($user->shop_id) {
            $categoriesQuery->where('shop_id', $user->shop_id);
        }
        $categories = $categoriesQuery->orderBy('name')->get()->unique('name');

        return view('admin.expenses.index', compact(
            'expenses',
            'categories',
            'shops',
            'todayTotal',
            'monthTotal',
            'pendingCount',
            'pendingTotal'
        ));
    }

    /**
     * Afficher une dépense
     */
    public function show(Expense $expense)
    {
        $user = Auth::user();
        
        // Vérifier l'accès
        if ($user->shop_id && $expense->shop_id !== $user->shop_id) {
            abort(403);
        }

        $expense->load(['category', 'user', 'approver', 'cashRegister', 'shop']);

        return view('admin.expenses.show', compact('expense'));
    }

    /**
     * Approuver une dépense
     */
    public function approve(Expense $expense)
    {
        $user = Auth::user();
        
        if ($user->shop_id && $expense->shop_id !== $user->shop_id) {
            abort(403);
        }

        if ($expense->status !== 'pending') {
            return back()->withErrors(['error' => 'Cette dépense ne peut pas être approuvée.']);
        }

        $expense->approve($user->id);

        return back()->with('success', 'Dépense approuvée avec succès.');
    }

    /**
     * Rejeter une dépense
     */
    public function reject(Expense $expense)
    {
        $user = Auth::user();
        
        if ($user->shop_id && $expense->shop_id !== $user->shop_id) {
            abort(403);
        }

        if ($expense->status !== 'pending') {
            return back()->withErrors(['error' => 'Cette dépense ne peut pas être rejetée.']);
        }

        $expense->reject($user->id);

        return back()->with('success', 'Dépense rejetée.');
    }

    /**
     * Tableau de bord des dépenses avec statistiques
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $shopId = $request->get('shop_id', $user->shop_id);

        // Boutiques pour le filtre
        $shops = $user->shop_id ? collect() : Shop::orderBy('name')->get();

        // Période par défaut : mois en cours
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $query = Expense::approved()->forPeriod($startDate, $endDate);
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        // Total des dépenses
        $totalExpenses = (clone $query)->sum('amount');

        // Dépenses par catégorie
        $expensesByCategory = (clone $query)
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->category->name ?? 'Sans catégorie',
                    'color' => $item->category->color ?? '#6c757d',
                    'icon' => $item->category->icon ?? 'fa-tag',
                    'total' => $item->total,
                ];
            });

        // Dépenses par mode de paiement
        $expensesByPaymentMethod = (clone $query)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        // Évolution journalière
        $dailyExpenses = (clone $query)
            ->select(DB::raw('DATE(expense_date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top 10 des plus grosses dépenses
        $topExpenses = (clone $query)
            ->with(['category', 'user'])
            ->orderBy('amount', 'desc')
            ->limit(10)
            ->get();

        // Comparaison avec le mois précédent
        $previousMonthStart = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();
        
        $previousMonthQuery = Expense::approved()->forPeriod($previousMonthStart, $previousMonthEnd);
        if ($shopId) {
            $previousMonthQuery->where('shop_id', $shopId);
        }
        $previousMonthTotal = $previousMonthQuery->sum('amount');

        $monthVariation = $previousMonthTotal > 0 
            ? (($totalExpenses - $previousMonthTotal) / $previousMonthTotal) * 100 
            : 0;

        // Catégories avec budget
        $categoriesQuery = ExpenseCategory::where('monthly_budget', '>', 0);
        if ($shopId) {
            $categoriesQuery->where('shop_id', $shopId);
        }
        $categoriesWithBudget = $categoriesQuery->get()->map(function ($category) {
            $spent = $category->currentMonthExpenses();
            return [
                'name' => $category->name,
                'color' => $category->color,
                'budget' => $category->monthly_budget,
                'spent' => $spent,
                'percentage' => $category->monthly_budget > 0 ? min(100, ($spent / $category->monthly_budget) * 100) : 0,
                'exceeded' => $spent > $category->monthly_budget,
            ];
        });

        return view('admin.expenses.dashboard', compact(
            'shops',
            'shopId',
            'startDate',
            'endDate',
            'totalExpenses',
            'expensesByCategory',
            'expensesByPaymentMethod',
            'dailyExpenses',
            'topExpenses',
            'previousMonthTotal',
            'monthVariation',
            'categoriesWithBudget'
        ));
    }

    /**
     * Gestion des catégories (Admin)
     */
    public function categories(Request $request)
    {
        $user = Auth::user();
        
        $query = ExpenseCategory::withCount('expenses');
        
        if ($user->shop_id) {
            $query->where('shop_id', $user->shop_id);
            $shops = collect();
        } else {
            $shops = Shop::orderBy('name')->get();
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
        }

        $categories = $query->orderBy('name')->get();

        // Calculer les dépenses du mois pour chaque catégorie
        foreach ($categories as $category) {
            $category->month_total = $category->currentMonthExpenses();
        }

        return view('admin.expenses.categories', compact('categories', 'shops'));
    }

    /**
     * Exporter les dépenses
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        $query = Expense::with(['category', 'user', 'shop'])
            ->approved()
            ->orderBy('expense_date', 'desc');

        if ($user->shop_id) {
            $query->where('shop_id', $user->shop_id);
        } elseif ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $expenses = $query->get();

        $filename = 'depenses_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($expenses) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            
            // En-têtes
            fputcsv($file, [
                'Référence',
                'Date',
                'Boutique',
                'Catégorie',
                'Description',
                'Bénéficiaire',
                'Montant',
                'Mode paiement',
                'Enregistré par',
                'Statut',
            ], ';');

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->reference,
                    $expense->expense_date->format('d/m/Y'),
                    $expense->shop->name ?? '-',
                    $expense->category->name ?? '-',
                    $expense->description,
                    $expense->beneficiary ?? '-',
                    number_format($expense->amount, 0, ',', ' '),
                    $expense->payment_method_label,
                    $expense->user->name ?? '-',
                    $expense->status_label,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

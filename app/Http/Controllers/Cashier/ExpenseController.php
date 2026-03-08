<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Afficher la liste des dépenses
     */
    public function index(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        
        $query = Expense::where('shop_id', $shopId)
            ->with(['category', 'user'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filtres
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

        $expenses = $query->paginate(20)->withQueryString();

        // Statistiques
        $todayTotal = Expense::where('shop_id', $shopId)
            ->today()
            ->approved()
            ->sum('amount');

        $monthTotal = Expense::where('shop_id', $shopId)
            ->currentMonth()
            ->approved()
            ->sum('amount');

        $pendingCount = Expense::where('shop_id', $shopId)
            ->pending()
            ->count();

        // Catégories pour filtre
        $categories = ExpenseCategory::where('shop_id', $shopId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('cashier.expenses.index', compact(
            'expenses',
            'categories',
            'todayTotal',
            'monthTotal',
            'pendingCount'
        ));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $shopId = Auth::user()->shop_id;

        $categories = ExpenseCategory::where('shop_id', $shopId)
            ->active()
            ->orderBy('name')
            ->get();

        // Vérifier si une caisse est ouverte
        $openCashRegister = CashRegister::where('shop_id', $shopId)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();

        return view('cashier.expenses.create', compact('categories', 'openCashRegister'));
    }

    /**
     * Enregistrer une nouvelle dépense
     */
    public function store(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'beneficiary' => 'nullable|string|max:255',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt_number' => 'nullable|string|max:100',
            'receipt_image' => 'nullable|image|max:2048',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,check',
        ]);

        // Vérifier que la catégorie appartient à la boutique
        $category = ExpenseCategory::where('shop_id', $shopId)
            ->findOrFail($validated['expense_category_id']);

        // Déterminer le statut initial
        $status = $category->requires_approval ? 'pending' : 'approved';

        // Récupérer la caisse ouverte si paiement en espèces
        $cashRegisterId = null;
        if ($validated['payment_method'] === 'cash') {
            $cashRegister = CashRegister::where('shop_id', $shopId)
                ->where('user_id', Auth::id())
                ->where('status', 'open')
                ->first();

            if (!$cashRegister) {
                return back()->withErrors(['cash_register' => 'Vous devez avoir une caisse ouverte pour les dépenses en espèces.']);
            }

            $cashRegisterId = $cashRegister->id;
        }

        DB::beginTransaction();
        try {
            // Upload de l'image du reçu
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('receipts', 'public');
            }

            // Créer la dépense
            $expense = Expense::create([
                'shop_id' => $shopId,
                'user_id' => Auth::id(),
                'cash_register_id' => $cashRegisterId,
                'expense_category_id' => $validated['expense_category_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'beneficiary' => $validated['beneficiary'] ?? null,
                'expense_date' => $validated['expense_date'],
                'receipt_number' => $validated['receipt_number'] ?? null,
                'receipt_image' => $receiptPath,
                'payment_method' => $validated['payment_method'],
                'status' => $status,
                'approved_by' => $status === 'approved' ? Auth::id() : null,
                'approved_at' => $status === 'approved' ? now() : null,
            ]);

            // Si paiement en espèces et approuvé, enregistrer la transaction de caisse
            if ($validated['payment_method'] === 'cash' && $status === 'approved' && $cashRegisterId) {
                CashTransaction::create([
                    'shop_id' => $shopId,
                    'cash_register_id' => $cashRegisterId,
                    'type' => CashTransaction::TYPE_EXPENSE,
                    'category' => CashTransaction::CATEGORY_EXPENSE,
                    'amount' => -$validated['amount'],
                    'description' => "Dépense: {$validated['description']}",
                    'reference_type' => Expense::class,
                    'reference_id' => $expense->id,
                    'user_id' => Auth::id(),
                ]);
            }

            // Log d'activité
            ActivityLog::log('expense', $expense, null, $expense->toArray(), "Dépense enregistrée: {$expense->reference}");

            DB::commit();

            $message = $status === 'pending' 
                ? 'Dépense enregistrée et en attente d\'approbation.' 
                : 'Dépense enregistrée avec succès.';

            return redirect()->route('cashier.expenses.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
        }
    }

    /**
     * Afficher une dépense
     */
    public function show(Expense $expense)
    {
        // Vérifier que l'utilisateur peut voir cette dépense (même boutique)
        if ($expense->shop_id !== Auth::user()->shop_id) {
            abort(403, 'Accès non autorisé.');
        }

        $expense->load(['category', 'user', 'approver', 'cashRegister']);

        return view('cashier.expenses.show', compact('expense'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Expense $expense)
    {
        // Seules les dépenses en attente peuvent être modifiées
        if ($expense->status !== 'pending' || $expense->shop_id !== Auth::user()->shop_id) {
            return back()->with('error', 'Cette dépense ne peut pas être modifiée.');
        }

        $shopId = Auth::user()->shop_id;

        $categories = ExpenseCategory::where('shop_id', $shopId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('cashier.expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Mettre à jour une dépense
     */
    public function update(Request $request, Expense $expense)
    {
        // Seules les dépenses en attente peuvent être modifiées
        if ($expense->status !== 'pending' || $expense->shop_id !== Auth::user()->shop_id) {
            return back()->with('error', 'Cette dépense ne peut pas être modifiée.');
        }

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'beneficiary' => 'nullable|string|max:255',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt_number' => 'nullable|string|max:100',
            'receipt_image' => 'nullable|image|max:2048',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,check',
        ]);

        DB::beginTransaction();
        try {
            $oldData = $expense->toArray();

            // Upload de l'image du reçu
            if ($request->hasFile('receipt_image')) {
                // Supprimer l'ancienne image
                if ($expense->receipt_image) {
                    Storage::disk('public')->delete($expense->receipt_image);
                }
                $validated['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
            }

            $expense->update($validated);

            // Log d'activité
            ActivityLog::log('expense', $expense, $oldData, $expense->fresh()->toArray(), "Dépense modifiée: {$expense->reference}");

            DB::commit();

            return redirect()->route('cashier.expenses.show', $expense)
                ->with('success', 'Dépense mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Supprimer une dépense
     */
    public function destroy(Expense $expense)
    {
        // Seules les dépenses en attente peuvent être supprimées
        if ($expense->status !== 'pending' || $expense->shop_id !== Auth::user()->shop_id) {
            return back()->with('error', 'Cette dépense ne peut pas être supprimée.');
        }

        DB::beginTransaction();
        try {
            // Supprimer l'image du reçu
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }

            // Log d'activité
            ActivityLog::log('expense', $expense, $expense->toArray(), null, "Dépense supprimée: {$expense->reference}");

            $expense->delete();

            DB::commit();

            return redirect()->route('cashier.expenses.index')
                ->with('success', 'Dépense supprimée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestion des catégories
     */
    public function categories()
    {
        $shopId = Auth::user()->shop_id;

        $categories = ExpenseCategory::where('shop_id', $shopId)
            ->withCount('expenses')
            ->orderBy('name')
            ->get();

        // Calculer les dépenses du mois pour chaque catégorie
        foreach ($categories as $category) {
            $category->month_total = $category->currentMonthExpenses();
        }

        return view('cashier.expenses.categories', compact('categories'));
    }

    /**
     * Enregistrer une nouvelle catégorie
     */
    public function storeCategory(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
            'monthly_budget' => 'nullable|numeric|min:0',
            'requires_approval' => 'boolean',
        ]);

        // Vérifier l'unicité
        $exists = ExpenseCategory::where('shop_id', $shopId)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Cette catégorie existe déjà.']);
        }

        ExpenseCategory::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'fa-tag',
            'color' => $validated['color'],
            'description' => $validated['description'] ?? null,
            'monthly_budget' => $validated['monthly_budget'] ?? null,
            'requires_approval' => $validated['requires_approval'] ?? false,
        ]);

        return back()->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory(Request $request, ExpenseCategory $category)
    {
        $shopId = Auth::user()->shop_id;

        if ($category->shop_id !== $shopId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
            'monthly_budget' => 'nullable|numeric|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Vérifier l'unicité
        $exists = ExpenseCategory::where('shop_id', $shopId)
            ->where('name', $validated['name'])
            ->where('id', '!=', $category->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Cette catégorie existe déjà.']);
        }

        $category->update($validated);

        return back()->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Supprimer une catégorie
     */
    public function destroyCategory(ExpenseCategory $category)
    {
        $shopId = Auth::user()->shop_id;

        if ($category->shop_id !== $shopId) {
            abort(403);
        }

        // Vérifier si des dépenses utilisent cette catégorie
        if ($category->expenses()->count() > 0) {
            return back()->withErrors(['category' => 'Cette catégorie contient des dépenses et ne peut pas être supprimée.']);
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée avec succès.');
    }
}

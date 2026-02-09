<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use Illuminate\Http\Request;

/**
 * Gestion de la caisse - Caissière uniquement
 */
class CashRegisterController extends Controller
{
    /**
     * Afficher la caisse actuelle
     */
    public function index()
    {
        $user = auth()->user();
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        // Historique des caisses
        $history = CashRegister::where('user_id', $user->id)
            ->with('transactions')
            ->latest()
            ->paginate(10);

        return view('cashier.cash-register.index', compact('cashRegister', 'history'));
    }

    /**
     * Formulaire d'ouverture de caisse
     */
    public function openForm()
    {
        $user = auth()->user();

        // Vérifier si une caisse est déjà ouverte
        if (CashRegister::getOpenRegisterForUser($user->id)) {
            return redirect()->route('cashier.cash-register.index')
                ->with('warning', 'Une caisse est déjà ouverte.');
        }

        // Vérifier si une caisse a déjà été fermée aujourd'hui
        $todayRegister = CashRegister::getTodayRegisterForUser($user->id);
        if ($todayRegister && $todayRegister->is_closed) {
            return redirect()->route('cashier.cash-register.index')
                ->with('error', 'Une caisse a déjà été fermée aujourd\'hui. Vous ne pouvez pas en ouvrir une nouvelle.');
        }

        // Récupérer le solde de la dernière clôture
        $lastRegister = CashRegister::where('user_id', $user->id)
            ->closed()
            ->latest()
            ->first();

        $suggestedBalance = $lastRegister?->closing_balance ?? 0;

        return view('cashier.cash-register.open', compact('suggestedBalance'));
    }

    /**
     * Ouvrir la caisse
     */
    public function open(Request $request)
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // Vérifier si une caisse est déjà ouverte
        if (CashRegister::getOpenRegisterForUser($user->id)) {
            return back()->with('error', 'Une caisse est déjà ouverte.');
        }

        // Vérifier si une caisse a déjà été fermée aujourd'hui
        $todayRegister = CashRegister::getTodayRegisterForUser($user->id);
        if ($todayRegister && $todayRegister->is_closed) {
            return redirect()->route('cashier.cash-register.index')
                ->with('error', 'Une caisse a déjà été fermée aujourd\'hui. Vous ne pouvez pas en ouvrir une nouvelle.');
        }

        try {
            $cashRegister = CashRegister::openRegister(
                $user,
                $validated['opening_balance'],
                $validated['notes'] ?? null
            );

            ActivityLog::log('create', $cashRegister, null, $cashRegister->toArray(), 'Ouverture de caisse');

            return redirect()->route('cashier.cash-register.index')
                ->with('success', 'Caisse ouverte avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Formulaire de clôture de caisse
     */
    public function closeForm()
    {
        $user = auth()->user();
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        if (!$cashRegister) {
            return redirect()->route('cashier.cash-register.index')
                ->with('error', 'Aucune caisse ouverte.');
        }

        $cashRegister->load('transactions');

        return view('cashier.cash-register.close', compact('cashRegister'));
    }

    /**
     * Clôturer la caisse
     */
    public function close(Request $request)
    {
        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        $cashRegister->close(
            $validated['closing_balance'],
            $validated['notes'] ?? null
        );

        ActivityLog::log('update', $cashRegister, null, $cashRegister->toArray(), 'Clôture de caisse');

        return redirect()->route('cashier.cash-register.index')
            ->with('success', 'Caisse clôturée avec succès.');
    }

    /**
     * Voir le détail d'une caisse
     */
    public function show(CashRegister $cashRegister)
    {
        // Vérifier que c'est bien la caisse de l'utilisateur
        if ($cashRegister->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $cashRegister->load(['transactions.transactionable', 'user']);

        // Regrouper les transactions par catégorie
        $transactionsByCategory = $cashRegister->transactions->groupBy('category');

        // Résumé par mode de paiement
        $byPaymentMethod = [
            'cash' => $cashRegister->transactions->where('payment_method', 'cash')->sum('amount'),
            'mobile_money' => $cashRegister->transactions->where('payment_method', 'mobile_money')->sum('amount'),
            'card' => $cashRegister->transactions->where('payment_method', 'card')->sum('amount'),
        ];

        return view('cashier.cash-register.show', compact('cashRegister', 'transactionsByCategory', 'byPaymentMethod'));
    }

    /**
     * Ajouter une dépense
     */
    public function addExpense(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'payment_method' => 'required|in:cash,mobile_money,card',
        ]);

        $user = auth()->user();
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        $transaction = $cashRegister->addTransaction(
            CashTransaction::TYPE_EXPENSE,
            CashTransaction::CATEGORY_EXPENSE,
            $validated['amount'],
            $validated['payment_method'],
            null,
            $validated['description']
        );

        ActivityLog::log('create', $transaction, null, $transaction->toArray(), "Dépense: {$validated['description']}");

        return back()->with('success', 'Dépense enregistrée.');
    }

    /**
     * Entrée de caisse
     */
    public function cashIn(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        $transaction = CashTransaction::create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => auth()->id(),
            'type' => CashTransaction::TYPE_INCOME,
            'category' => CashTransaction::CATEGORY_ADJUSTMENT,
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? 'Entrée de caisse',
        ]);

        ActivityLog::log('create', $transaction, null, $transaction->toArray(), "Entrée de caisse: {$validated['amount']} FCFA");

        return back()->with('success', 'Entrée de caisse enregistrée.');
    }

    /**
     * Sortie de caisse
     */
    public function cashOut(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        $transaction = CashTransaction::create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => auth()->id(),
            'type' => CashTransaction::TYPE_EXPENSE,
            'category' => CashTransaction::CATEGORY_ADJUSTMENT,
            'amount' => -abs($validated['amount']),
            'description' => $validated['description'],
        ]);

        ActivityLog::log('create', $transaction, null, $transaction->toArray(), "Sortie de caisse: {$validated['amount']} FCFA - {$validated['description']}");

        return back()->with('success', 'Sortie de caisse enregistrée.');
    }
}

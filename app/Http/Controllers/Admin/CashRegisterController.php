<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Gestion des caisses par l'admin
 * Avec filtre par boutique
 */
class CashRegisterController extends Controller
{
    /**
     * Liste de toutes les caisses
     */
    public function index(Request $request)
    {
        $query = CashRegister::withoutGlobalScope('shop')->with(['user', 'transactions', 'shop']);

        // Filtre par boutique
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filtres
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $cashRegisters = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $cashiers = User::role('caissiere')->where('is_active', true)->get();
        $shops = Shop::active()->orderBy('name')->get();

        return view('admin.cash-registers.index', compact('cashRegisters', 'cashiers', 'shops'));
    }

    /**
     * Voir les détails d'une caisse
     */
    public function show(CashRegister $cashRegister)
    {
        $cashRegister->load(['user', 'transactions', 'shop']);

        return view('admin.cash-registers.show', compact('cashRegister'));
    }

    /**
     * Réouvrir une caisse fermée
     */
    public function reopen(CashRegister $cashRegister)
    {
        if ($cashRegister->status !== 'closed') {
            return back()->with('error', 'Cette caisse n\'est pas fermée.');
        }

        // Vérifier s'il n'y a pas déjà une caisse ouverte pour cet utilisateur
        $openRegister = CashRegister::getOpenRegisterForUser($cashRegister->user_id);
        if ($openRegister) {
            return back()->with('error', 'Cet utilisateur a déjà une caisse ouverte. Fermez-la d\'abord.');
        }

        $oldData = $cashRegister->toArray();

        $cashRegister->update([
            'status' => 'open',
            'closed_at' => null,
            'closing_balance' => null,
            'expected_balance' => null,
            'difference' => null,
            'closing_notes' => null,
        ]);

        ActivityLog::log(
            'update',
            $cashRegister,
            $oldData,
            $cashRegister->fresh()->toArray(),
            'Réouverture de caisse par admin'
        );

        return back()->with('success', 'Caisse réouverte avec succès.');
    }

    /**
     * Supprimer une caisse (seulement si vide)
     */
    public function destroy(CashRegister $cashRegister)
    {
        // Vérifier si la caisse a des transactions
        if ($cashRegister->transactions()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une caisse avec des transactions. Vous pouvez la réouvrir à la place.');
        }

        $cashRegister->delete();

        ActivityLog::log('delete', $cashRegister, $cashRegister->toArray(), null, 'Suppression de caisse vide par admin');

        return redirect()->route('admin.cash-registers.index')
            ->with('success', 'Caisse supprimée avec succès.');
    }

    /**
     * Forcer la fermeture d'une caisse
     */
    public function forceClose(Request $request, CashRegister $cashRegister)
    {
        if ($cashRegister->status !== 'open') {
            return back()->with('error', 'Cette caisse n\'est pas ouverte.');
        }

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldData = $cashRegister->toArray();

        $expectedBalance = $cashRegister->calculated_balance;
        $difference = $validated['closing_balance'] - $expectedBalance;

        $cashRegister->update([
            'closing_balance' => $validated['closing_balance'],
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
            'closing_notes' => ($validated['notes'] ?? '') . ' [Fermée par admin]',
        ]);

        ActivityLog::log(
            'update',
            $cashRegister,
            $oldData,
            $cashRegister->fresh()->toArray(),
            'Fermeture forcée de caisse par admin'
        );

        return back()->with('success', 'Caisse fermée de force avec succès.');
    }

    /**
     * Export CSV des caisses
     */
    public function export(Request $request)
    {
        $query = CashRegister::with(['user', 'transactions']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $cashRegisters = $query->orderBy('date', 'desc')->get();

        $filename = 'caisses_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($cashRegisters) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Date',
                'Caissière',
                'Ouverture',
                'Fermeture',
                'Solde ouverture',
                'Solde attendu',
                'Solde fermeture',
                'Différence',
                'Statut',
                'Nb transactions',
            ], ';');

            foreach ($cashRegisters as $cr) {
                fputcsv($file, [
                    $cr->date->format('d/m/Y'),
                    $cr->user?->name ?? 'N/A',
                    $cr->opened_at?->format('H:i') ?? '-',
                    $cr->closed_at?->format('H:i') ?? '-',
                    number_format($cr->opening_balance, 0, ',', ' '),
                    number_format($cr->expected_balance ?? 0, 0, ',', ' '),
                    number_format($cr->closing_balance ?? 0, 0, ',', ' '),
                    number_format($cr->difference ?? 0, 0, ',', ' '),
                    $cr->status === 'open' ? 'Ouverte' : 'Fermée',
                    $cr->transactions->count(),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

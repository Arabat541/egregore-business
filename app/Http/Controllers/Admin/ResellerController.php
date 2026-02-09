<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Reseller;
use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * Gestion des revendeurs - Admin (paramétrage) et Caissière (utilisation)
 */
class ResellerController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = auth()->user()->hasRole('admin');
        
        // Admin voit tous les revendeurs, autres voient seulement leur boutique
        if ($isAdmin) {
            $query = Reseller::withoutGlobalScope('shop')->with('shop');
            
            // Filtre par boutique si spécifié
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
        } else {
            $query = Reseller::query();
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->active(),
                'inactive' => $query->where('is_active', false),
                'with_debt' => $query->withDebt(),
                default => null,
            };
        }

        $resellers = $query->latest()->paginate(15);
        $shops = $isAdmin ? Shop::active()->orderBy('name')->get() : collect();

        return view('admin.resellers.index', compact('resellers', 'shops'));
    }

    public function create()
    {
        return view('admin.resellers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:resellers,phone',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Gérer les checkboxes explicitement
        $validated['is_active'] = $request->has('is_active');
        $validated['credit_allowed'] = $request->has('credit_allowed');

        $reseller = Reseller::create($validated);

        ActivityLog::log('create', $reseller, null, $reseller->toArray(), "Création revendeur: {$reseller->company_name}");

        return redirect()->route('admin.resellers.index')
            ->with('success', 'Revendeur créé avec succès.');
    }

    public function show(Reseller $reseller)
    {
        $reseller->load([
            'sales' => fn($q) => $q->latest()->take(10),
            'payments' => fn($q) => $q->latest()->take(10),
        ]);

        return view('admin.resellers.show', compact('reseller'));
    }

    public function edit(Reseller $reseller)
    {
        return view('admin.resellers.edit', compact('reseller'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:resellers,phone,' . $reseller->id,
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Gérer les checkboxes explicitement (non envoyés si décochés)
        $validated['is_active'] = $request->has('is_active');
        $validated['credit_allowed'] = $request->has('credit_allowed');

        $oldValues = $reseller->toArray();
        $reseller->update($validated);

        ActivityLog::log('update', $reseller, $oldValues, $reseller->toArray(), "Modification revendeur: {$reseller->company_name}");

        return redirect()->route('admin.resellers.index')
            ->with('success', 'Revendeur mis à jour avec succès.');
    }

    public function destroy(Reseller $reseller)
    {
        // Vérifier si le revendeur a des ventes
        if ($reseller->sales()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce revendeur car il a des ventes enregistrées.');
        }

        ActivityLog::log('delete', $reseller, $reseller->toArray(), null, "Suppression revendeur: {$reseller->company_name}");

        $reseller->delete();

        return redirect()->route('admin.resellers.index')
            ->with('success', 'Revendeur supprimé avec succès.');
    }

    /**
     * Modifier le plafond de crédit
     */
    public function updateCreditLimit(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'credit_allowed' => 'boolean',
        ]);

        $oldValues = [
            'credit_limit' => $reseller->credit_limit,
            'credit_allowed' => $reseller->credit_allowed,
        ];

        $reseller->update($validated);

        ActivityLog::log('update', $reseller, $oldValues, $validated, "Modification plafond crédit: {$reseller->company_name}");

        return back()->with('success', 'Plafond de crédit mis à jour.');
    }

    /**
     * Afficher le relevé de compte d'un revendeur
     */
    public function accountStatement(Request $request, Reseller $reseller)
    {
        $startDate = $request->filled('start_date') 
            ? $request->start_date 
            : now()->startOfYear()->format('Y-m-d');
        $endDate = $request->filled('end_date') 
            ? $request->end_date 
            : now()->format('Y-m-d');

        // Créance d'ouverture (dette au début de la période)
        // = Total des ventes avant la période - Total des paiements avant la période
        $salesBeforePeriod = $reseller->sales()
            ->where('created_at', '<', $startDate)
            ->sum('total_amount');
        $paymentsBeforePeriod = $reseller->payments()
            ->where('created_at', '<', $startDate)
            ->sum('amount');
        // On ajoute aussi les amount_paid des ventes (acomptes versés au moment de la vente)
        $amountPaidBeforePeriod = $reseller->sales()
            ->where('created_at', '<', $startDate)
            ->sum('amount_paid');
        
        $openingBalance = $salesBeforePeriod - $paymentsBeforePeriod - $amountPaidBeforePeriod;

        // Récupérer les ventes sur la période
        $sales = $reseller->sales()
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->with('items.product')
            ->orderBy('created_at')
            ->get();

        // Récupérer les paiements sur la période
        $payments = $reseller->payments()
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        // Fusionner et trier les mouvements
        $movements = collect();

        foreach ($sales as $sale) {
            $products = $sale->items->map(fn($item) => [
                'name' => $item->product->name ?? 'Produit supprimé',
                'quantity' => $item->quantity,
                'total' => $item->total_price,
            ])->toArray();

            // Débit = montant total de la vente (dette générée)
            $movements->push([
                'date' => $sale->created_at,
                'type' => 'sale',
                'reference' => $sale->reference ?? 'VTE-' . $sale->id,
                'sale_id' => $sale->id,
                'description' => 'Vente - ' . count($sale->items) . ' article(s)',
                'products' => $products,
                'debit' => $sale->total_amount,
                'credit' => 0,
            ]);

            // Si un acompte a été versé au moment de la vente, l'ajouter comme crédit
            if ($sale->amount_paid > 0) {
                $movements->push([
                    'date' => $sale->created_at,
                    'type' => 'payment',
                    'reference' => 'ACP-' . $sale->id,
                    'description' => 'Acompte sur vente VTE-' . $sale->id,
                    'debit' => 0,
                    'credit' => $sale->amount_paid,
                ]);
            }
        }

        foreach ($payments as $payment) {
            $movements->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'reference' => $payment->reference ?? 'PAY-' . $payment->id,
                'description' => 'Paiement - ' . ucfirst($payment->payment_method ?? 'Espèces'),
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }

        $movements = $movements->sortBy('date')->values();

        // Calculer le résumé
        $totalPurchases = $sales->sum('total_amount');
        $totalPayments = $payments->sum('amount') + $sales->sum('amount_paid');
        
        $summary = [
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'total_discount' => $sales->sum(fn($s) => $s->discount_amount ?? 0),
            'balance' => $totalPurchases - $totalPayments,
        ];

        return view('admin.resellers.statement', compact(
            'reseller', 
            'movements', 
            'startDate', 
            'endDate',
            'openingBalance',
            'summary'
        ));
    }

    /**
     * Afficher le rapport de fidélité
     */
    public function loyaltyReport(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $isAdmin = auth()->user()->hasRole('admin');

        // Query des revendeurs actifs
        if ($isAdmin) {
            $query = Reseller::withoutGlobalScope('shop')->active()->with('shop');
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
        } else {
            $query = Reseller::active()->with('shop');
        }

        $resellers = $query->get();

        // Calculer les données de fidélité pour chaque revendeur
        $resellerData = [];
        $startOfYear = "$year-01-01";
        $endOfYear = "$year-12-31 23:59:59";

        foreach ($resellers as $reseller) {
            // Total des achats payés de l'année
            $totalPurchases = $reseller->sales()
                ->whereBetween('created_at', [$startOfYear, $endOfYear])
                ->sum('total_amount');
            
            $totalPaid = $reseller->sales()
                ->whereBetween('created_at', [$startOfYear, $endOfYear])
                ->sum('amount_paid');

            // Déterminer le niveau et taux de bonus
            $tier = 'bronze';
            $rate = 0;
            
            if ($totalPaid >= 5000000) {
                $tier = 'diamond';
                $rate = 5;
            } elseif ($totalPaid >= 2500000) {
                $tier = 'platinum';
                $rate = 4;
            } elseif ($totalPaid >= 1000000) {
                $tier = 'gold';
                $rate = 3;
            } elseif ($totalPaid >= 500000) {
                $tier = 'silver';
                $rate = 2;
            }

            $bonus = round($totalPaid * $rate / 100);

            // Vérifier si le bonus a déjà été versé
            $isPaid = \App\Models\ResellerLoyaltyBonus::where('reseller_id', $reseller->id)
                ->where('year', $year)
                ->where('status', 'paid')
                ->exists();

            $resellerData[$reseller->id] = [
                'total_purchases' => $totalPurchases,
                'total_paid' => $totalPaid,
                'tier' => $tier,
                'rate' => $rate,
                'bonus' => $bonus,
                'is_paid' => $isPaid,
            ];
        }

        // Statistiques globales
        $stats = [
            'active_resellers' => $resellers->count(),
            'total_revenue' => collect($resellerData)->sum('total_purchases'),
            'total_paid_revenue' => collect($resellerData)->sum('total_paid'),
            'total_bonus' => collect($resellerData)->sum('bonus'),
            'paid_bonus' => \App\Models\ResellerLoyaltyBonus::where('year', $year)
                ->where('status', 'paid')
                ->sum('bonus_amount'),
        ];

        // Historique des bonus versés cette année
        $bonusHistory = \App\Models\ResellerLoyaltyBonus::with(['reseller', 'paidBy'])
            ->where('year', $year)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->get();

        $shops = $isAdmin ? Shop::active()->orderBy('name')->get() : collect();

        return view('admin.resellers.loyalty', compact(
            'resellers',
            'resellerData',
            'year',
            'stats',
            'bonusHistory',
            'shops'
        ));
    }

    /**
     * Verser un bonus de fidélité
     */
    public function payBonus(Request $request)
    {
        $validated = $request->validate([
            'reseller_id' => 'required|exists:resellers,id',
            'year' => 'required|integer',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,credit',
            'notes' => 'nullable|string',
        ]);

        $reseller = Reseller::findOrFail($validated['reseller_id']);
        $year = $validated['year'];

        // Calculer le bonus
        $startOfYear = "$year-01-01";
        $endOfYear = "$year-12-31 23:59:59";
        
        $totalPaid = $reseller->sales()
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->sum('amount_paid');

        // Déterminer le taux
        $tier = 'bronze';
        $rate = 0;
        
        if ($totalPaid >= 5000000) {
            $tier = 'diamond';
            $rate = 5;
        } elseif ($totalPaid >= 2500000) {
            $tier = 'platinum';
            $rate = 4;
        } elseif ($totalPaid >= 1000000) {
            $tier = 'gold';
            $rate = 3;
        } elseif ($totalPaid >= 500000) {
            $tier = 'silver';
            $rate = 2;
        }

        $bonusAmount = round($totalPaid * $rate / 100);

        // Vérifier si déjà payé
        $exists = \App\Models\ResellerLoyaltyBonus::where('reseller_id', $reseller->id)
            ->where('year', $year)
            ->where('status', 'paid')
            ->exists();

        if ($exists) {
            return back()->with('error', 'Le bonus a déjà été versé pour cette année.');
        }

        // Créer l'enregistrement du bonus
        $bonus = \App\Models\ResellerLoyaltyBonus::updateOrCreate(
            ['reseller_id' => $reseller->id, 'year' => $year],
            [
                'yearly_purchases' => $totalPaid,
                'tier' => $tier,
                'bonus_rate' => $rate,
                'bonus_amount' => $bonusAmount,
                'status' => 'paid',
                'payment_method' => $validated['payment_method'],
                'paid_at' => now(),
                'paid_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        ActivityLog::log('create', $bonus, null, $bonus->toArray(), 
            "Versement bonus fidélité: {$reseller->company_name} - {$bonusAmount} F ({$year})");

        return back()->with('success', "Bonus de {$bonusAmount} F versé à {$reseller->company_name}.");
    }

    /**
     * Générer les bonus de fidélité pour l'année
     */
    public function generateLoyaltyBonuses(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : now()->year - 1;

        // Récupérer tous les revendeurs actifs avec un taux de bonus > 0
        $resellers = Reseller::withoutGlobalScope('shop')
            ->active()
            ->where('loyalty_bonus_rate', '>', 0)
            ->get();

        $created = 0;
        foreach ($resellers as $reseller) {
            // Vérifier si un bonus existe déjà pour cette année
            $exists = \App\Models\ResellerLoyaltyBonus::where('reseller_id', $reseller->id)
                ->where('year', $year)
                ->exists();

            if (!$exists && $reseller->total_purchases_year > 0) {
                \App\Models\ResellerLoyaltyBonus::create([
                    'reseller_id' => $reseller->id,
                    'year' => $year,
                    'total_purchases' => $reseller->total_purchases_year,
                    'bonus_rate' => $reseller->loyalty_bonus_rate,
                    'bonus_amount' => $reseller->expected_bonus,
                    'status' => 'pending',
                ]);
                $created++;
            }
        }

        return back()->with('success', "$created bonus de fidélité générés pour l'année $year.");
    }

    /**
     * Approuver un bonus de fidélité
     */
    public function approveLoyaltyBonus(\App\Models\ResellerLoyaltyBonus $bonus)
    {
        $bonus->approve(auth()->id());

        ActivityLog::log('update', $bonus, ['status' => 'pending'], ['status' => 'approved'], 
            "Approbation bonus fidélité: {$bonus->reseller->company_name} - {$bonus->year}");

        return back()->with('success', 'Bonus approuvé avec succès.');
    }

    /**
     * Payer un bonus de fidélité
     */
    public function payLoyaltyBonus(Request $request, \App\Models\ResellerLoyaltyBonus $bonus)
    {
        $validated = $request->validate([
            'payment_type' => 'required|in:cash,credit,discount',
        ]);

        $bonus->markAsPaid($validated['payment_type']);

        ActivityLog::log('update', $bonus, ['status' => 'approved'], ['status' => 'paid'], 
            "Paiement bonus fidélité: {$bonus->reseller->company_name} - {$bonus->year} ({$bonus->payment_type_label})");

        return back()->with('success', 'Bonus payé avec succès.');
    }

    /**
     * Exporter le relevé de compte en PDF
     */
    public function exportAccountStatement(Request $request, Reseller $reseller)
    {
        $startDate = $request->filled('start_date') 
            ? $request->start_date 
            : now()->startOfYear()->format('Y-m-d');
        $endDate = $request->filled('end_date') 
            ? $request->end_date 
            : now()->format('Y-m-d');

        // Créance d'ouverture
        $salesBeforePeriod = $reseller->sales()
            ->where('created_at', '<', $startDate)
            ->sum('total_amount');
        $paymentsBeforePeriod = $reseller->payments()
            ->where('created_at', '<', $startDate)
            ->sum('amount');
        $amountPaidBeforePeriod = $reseller->sales()
            ->where('created_at', '<', $startDate)
            ->sum('amount_paid');
        
        $openingBalance = $salesBeforePeriod - $paymentsBeforePeriod - $amountPaidBeforePeriod;

        // Ventes sur la période
        $sales = $reseller->sales()
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->with('items.product')
            ->orderBy('created_at')
            ->get();

        // Paiements sur la période
        $payments = $reseller->payments()
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        // Fusionner les mouvements
        $movements = collect();

        foreach ($sales as $sale) {
            $products = $sale->items->map(fn($item) => [
                'name' => $item->product->name ?? 'Produit supprimé',
                'quantity' => $item->quantity,
                'total' => $item->total_price,
            ])->toArray();

            $movements->push([
                'date' => $sale->created_at,
                'type' => 'sale',
                'reference' => $sale->reference ?? 'VTE-' . $sale->id,
                'description' => 'Vente - ' . count($sale->items) . ' article(s)',
                'products' => $products,
                'debit' => $sale->total_amount,
                'credit' => 0,
            ]);

            if ($sale->amount_paid > 0) {
                $movements->push([
                    'date' => $sale->created_at,
                    'type' => 'payment',
                    'reference' => 'ACP-' . $sale->id,
                    'description' => 'Acompte sur vente VTE-' . $sale->id,
                    'debit' => 0,
                    'credit' => $sale->amount_paid,
                ]);
            }
        }

        foreach ($payments as $payment) {
            $movements->push([
                'date' => $payment->created_at,
                'type' => 'payment',
                'reference' => $payment->reference ?? 'PAY-' . $payment->id,
                'description' => 'Paiement - ' . ucfirst($payment->payment_method ?? 'Espèces'),
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }

        $movements = $movements->sortBy('date')->values();

        // Résumé
        $totalPurchases = $sales->sum('total_amount');
        $totalPayments = $payments->sum('amount') + $sales->sum('amount_paid');
        
        $summary = [
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'total_discount' => $sales->sum(fn($s) => $s->discount_amount ?? 0),
            'balance' => $totalPurchases - $totalPayments,
        ];

        // Infos boutique
        $shop = $reseller->shop;
        $shopName = $shop?->name ?? 'EGREGORE BUSINESS';
        $shopAddress = $shop?->address ?? '';
        $shopPhone = $shop?->phone ?? '';

        // Générer le PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.resellers.statement-pdf', compact(
            'reseller',
            'movements',
            'startDate',
            'endDate',
            'openingBalance',
            'summary',
            'shopName',
            'shopAddress',
            'shopPhone'
        ));

        $pdf->setPaper('A4', 'portrait');

        $filename = 'Releve_' . str_replace(' ', '_', $reseller->company_name) . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}

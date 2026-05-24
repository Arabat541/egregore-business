<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Reseller;
use App\Models\ResellerLoyaltyBonus;
use App\Models\Shop;
use App\Services\ResellerLoyaltyService;
use Illuminate\Http\Request;

/**
 * Gestion des revendeurs - Admin (paramétrage) et Caissière (utilisation)
 */
class ResellerController extends Controller
{
    public function __construct(
        private readonly ResellerLoyaltyService $loyaltyService,
    ) {}

    public function index(Request $request)
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $query   = Reseller::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active'    => $query->active(),
                'inactive'  => $query->where('is_active', false),
                'with_debt' => $query->withDebt(),
                default     => null,
            };
        }

        $resellers   = $query->latest()->paginate(15);
        $routePrefix = $isAdmin ? 'admin' : 'cashier';

        return view('admin.resellers.index', compact('resellers', 'routePrefix'));
    }

    public function create()
    {
        $isAdmin     = auth()->user()->hasRole('admin');
        $routePrefix = $isAdmin ? 'admin' : 'cashier';
        return view('admin.resellers.create', compact('routePrefix'));
    }

    public function store(Request $request)
    {
        $isAdmin = auth()->user()->hasRole('admin');

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone'        => ['required', 'string', \Illuminate\Validation\Rule::unique('resellers')],
            'email'        => 'nullable|email',
            'address'      => 'nullable|string',
            'tax_number'   => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        $validated['is_active']      = $request->has('is_active');
        $validated['credit_allowed'] = $request->has('credit_allowed');

        $reseller = Reseller::create($validated);

        ActivityLog::log('create', $reseller, null, $reseller->toArray(), "Création réparateur: {$reseller->company_name}");

        $route = $isAdmin ? 'admin.resellers.index' : 'cashier.resellers.index';
        return redirect()->route($route)->with('success', 'Réparateur créé avec succès.');
    }

    public function show(Reseller $reseller)
    {
        $reseller->load([
            'sales'    => fn($q) => $q->latest()->take(10),
            'payments' => fn($q) => $q->latest()->take(10),
        ]);

        $isAdmin     = auth()->user()->hasRole('admin');
        $routePrefix = $isAdmin ? 'admin' : 'cashier';

        return view('admin.resellers.show', compact('reseller', 'routePrefix'));
    }

    public function edit(Reseller $reseller)
    {
        $isAdmin     = auth()->user()->hasRole('admin');
        $routePrefix = $isAdmin ? 'admin' : 'cashier';
        return view('admin.resellers.edit', compact('reseller', 'routePrefix'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $isAdmin = auth()->user()->hasRole('admin');

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone'        => ['required', 'string', \Illuminate\Validation\Rule::unique('resellers')->ignore($reseller->id)],
            'email'        => 'nullable|email',
            'address'      => 'nullable|string',
            'tax_number'   => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        $validated['is_active']      = $request->has('is_active');
        $validated['credit_allowed'] = $request->has('credit_allowed');

        $oldValues = $reseller->toArray();
        $reseller->update($validated);

        ActivityLog::log('update', $reseller, $oldValues, $reseller->toArray(), "Modification réparateur: {$reseller->company_name}");

        $route = $isAdmin ? 'admin.resellers.index' : 'cashier.resellers.index';
        return redirect()->route($route)->with('success', 'Réparateur mis à jour avec succès.');
    }

    public function destroy(Reseller $reseller)
    {
        $isAdmin = auth()->user()->hasRole('admin');

        if ($reseller->sales()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce réparateur car il a des ventes enregistrées.');
        }

        ActivityLog::log('delete', $reseller, $reseller->toArray(), null, "Suppression réparateur: {$reseller->company_name}");
        $reseller->delete();

        $route = $isAdmin ? 'admin.resellers.index' : 'cashier.resellers.index';
        return redirect()->route($route)->with('success', 'Réparateur supprimé avec succès.');
    }

    public function updateCreditLimit(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'credit_limit'   => 'required|numeric|min:0',
            'credit_allowed' => 'boolean',
        ]);

        $oldValues = [
            'credit_limit'   => $reseller->credit_limit,
            'credit_allowed' => $reseller->credit_allowed,
        ];

        $reseller->update($validated);
        ActivityLog::log('update', $reseller, $oldValues, $validated, "Modification plafond crédit: {$reseller->company_name}");

        return back()->with('success', 'Plafond de crédit mis à jour.');
    }

    // ─────────────────────────────────────────────────────────
    //  Relevé de compte
    // ─────────────────────────────────────────────────────────

    public function accountStatement(Request $request, Reseller $reseller)
    {
        $startDate = $request->filled('start_date')
            ? $request->start_date
            : now()->startOfYear()->format('Y-m-d');
        $endDate = $request->filled('end_date')
            ? $request->end_date
            : now()->format('Y-m-d');
        $shopId = $request->filled('shop_id') ? (int) $request->shop_id : null;

        [
            'openingBalance' => $openingBalance,
            'movements'      => $movements,
            'payments'       => $payments,
            'summary'        => $summary,
        ] = $this->loyaltyService->buildStatement($reseller, $startDate, $endDate, $shopId);

        $shops = Shop::active()->orderBy('name')->get();

        return view('admin.resellers.statement', compact(
            'reseller', 'movements', 'payments', 'startDate', 'endDate', 'openingBalance', 'summary', 'shops', 'shopId'
        ));
    }

    public function exportAccountStatement(Request $request, Reseller $reseller)
    {
        $startDate = $request->filled('start_date')
            ? $request->start_date
            : now()->startOfYear()->format('Y-m-d');
        $endDate = $request->filled('end_date')
            ? $request->end_date
            : now()->format('Y-m-d');
        $shopId = $request->filled('shop_id') ? (int) $request->shop_id : null;

        [
            'openingBalance' => $openingBalance,
            'movements'      => $movements,
            'payments'       => $payments,
            'summary'        => $summary,
        ] = $this->loyaltyService->buildStatement($reseller, $startDate, $endDate, $shopId);

        if ($shopId) {
            $shop        = Shop::find($shopId);
            $shopName    = $shop?->name ?? 'EGREGORE BUSINESS';
            $shopAddress = $shop?->address ?? '';
            $shopPhone   = $shop?->phone ?? '';
        } else {
            $shopName    = 'Toutes boutiques';
            $shopAddress = '';
            $shopPhone   = '';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.resellers.statement-pdf', compact(
            'reseller', 'movements', 'payments', 'startDate', 'endDate',
            'openingBalance', 'summary',
            'shopName', 'shopAddress', 'shopPhone', 'shopId'
        ))->setPaper('A4', 'portrait');

        $filename = 'Releve_' . str_replace(' ', '_', $reseller->company_name) . '_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────
    //  Rapport de fidélité
    // ─────────────────────────────────────────────────────────

    public function loyaltyReport(Request $request)
    {
        $year    = $request->filled('year') ? (int) $request->year : now()->year;
        $isAdmin = auth()->user()->hasRole('admin');

        $query = Reseller::active();

        if ($request->filled('shop_id')) {
            $shopIdFilter = (int) $request->shop_id;
            $query->whereHas('sales', fn($q) => $q->withoutGlobalScope('shop')->where('shop_id', $shopIdFilter));
        }

        $resellers    = $query->get();
        $resellerData = [];

        foreach ($resellers as $reseller) {
            $resellerData[$reseller->id] = $this->loyaltyService->getLoyaltyData($reseller, $year);
        }

        $stats = [
            'active_resellers'   => $resellers->count(),
            'total_revenue'      => collect($resellerData)->sum('total_purchases'),
            'total_paid_revenue' => collect($resellerData)->sum('total_paid'),
            'total_bonus'        => collect($resellerData)->sum('bonus'),
            'paid_bonus'         => ResellerLoyaltyBonus::where('year', $year)->where('status', 'paid')->sum('bonus_amount'),
        ];

        $bonusHistory = ResellerLoyaltyBonus::with(['reseller', 'paidBy'])
            ->where('year', $year)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->get();

        $shops = Shop::active()->orderBy('name')->get();

        return view('admin.resellers.loyalty', compact(
            'resellers', 'resellerData', 'year', 'stats', 'bonusHistory', 'shops'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  Gestion des bonus
    // ─────────────────────────────────────────────────────────

    public function payBonus(Request $request)
    {
        $validated = $request->validate([
            'reseller_id'    => 'required|exists:resellers,id',
            'year'           => 'required|integer',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer,credit',
            'notes'          => 'nullable|string',
        ]);

        $reseller = Reseller::findOrFail($validated['reseller_id']);
        $year     = $validated['year'];

        $data = $this->loyaltyService->getLoyaltyData($reseller, $year);

        if ($data['is_paid']) {
            return back()->with('error', 'Le bonus a déjà été versé pour cette année.');
        }

        $bonus = ResellerLoyaltyBonus::updateOrCreate(
            ['reseller_id' => $reseller->id, 'year' => $year],
            [
                'yearly_purchases' => $data['total_paid'],
                'tier'             => $data['tier'],
                'bonus_rate'       => $data['rate'],
                'bonus_amount'     => $data['bonus'],
                'status'           => 'paid',
                'payment_method'   => $validated['payment_method'],
                'paid_at'          => now(),
                'paid_by'          => auth()->id(),
                'notes'            => $validated['notes'] ?? null,
            ]
        );

        ActivityLog::log('create', $bonus, null, $bonus->toArray(),
            "Versement bonus fidélité: {$reseller->company_name} - {$data['bonus']} F ({$year})");

        return back()->with('success', "Bonus de {$data['bonus']} F versé à {$reseller->company_name}.");
    }

    public function generateLoyaltyBonuses(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : now()->year - 1;

        $resellers = Reseller::active()
            ->where('loyalty_bonus_rate', '>', 0)
            ->get();

        $created = 0;
        foreach ($resellers as $reseller) {
            $exists = ResellerLoyaltyBonus::where('reseller_id', $reseller->id)
                ->where('year', $year)->exists();

            if (!$exists && $reseller->total_purchases_year > 0) {
                ResellerLoyaltyBonus::create([
                    'reseller_id'     => $reseller->id,
                    'year'            => $year,
                    'total_purchases' => $reseller->total_purchases_year,
                    'bonus_rate'      => $reseller->loyalty_bonus_rate,
                    'bonus_amount'    => $reseller->expected_bonus,
                    'status'          => 'pending',
                ]);
                $created++;
            }
        }

        return back()->with('success', "$created bonus de fidélité générés pour l'année $year.");
    }

    public function approveLoyaltyBonus(ResellerLoyaltyBonus $bonus)
    {
        $bonus->approve(auth()->id());

        ActivityLog::log('update', $bonus, ['status' => 'pending'], ['status' => 'approved'],
            "Approbation bonus fidélité: {$bonus->reseller->company_name} - {$bonus->year}");

        return back()->with('success', 'Bonus approuvé avec succès.');
    }

    public function payLoyaltyBonus(Request $request, ResellerLoyaltyBonus $bonus)
    {
        $validated = $request->validate([
            'payment_type' => 'required|in:cash,credit,discount',
        ]);

        $bonus->markAsPaid($validated['payment_type']);

        ActivityLog::log('update', $bonus, ['status' => 'approved'], ['status' => 'paid'],
            "Paiement bonus fidélité: {$bonus->reseller->company_name} - {$bonus->year} ({$bonus->payment_type_label})");

        return back()->with('success', 'Bonus payé avec succès.');
    }
}

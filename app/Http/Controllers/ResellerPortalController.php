<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductReturn;
use App\Models\Reseller;
use App\Services\ResellerLoyaltyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ResellerPortalController extends Controller
{
    public function __construct(
        private readonly ResellerLoyaltyService $loyaltyService,
    ) {}

    public function index(Request $request)
    {
        if ($request->session()->has('reseller_portal_id')) {
            return redirect()->route('reseller-portal.dashboard');
        }

        return view('public.reseller-portal.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        // Normalize: strip spaces, dashes, dots, parentheses
        $phone  = preg_replace('/[\s\-\.\(\)]+/', '', $request->phone);
        $digits = preg_replace('/[^0-9]/', '', $phone); // purely numeric variant

        $reseller = Reseller::where('is_active', true)
            ->where(fn($q) =>
                $q->where('phone', $phone)
                  ->orWhere('phone', $digits)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'.','') = ?", [$digits])
            )
            ->first();

        if (! $reseller) {
            return back()->withErrors(['phone' => 'Aucun compte trouvé pour ce numéro de téléphone.']);
        }

        $request->session()->regenerate(); // prevent session fixation
        $request->session()->put('reseller_portal_id', $reseller->id);
        $request->session()->put('reseller_portal_name', $reseller->company_name);

        return redirect()->route('reseller-portal.dashboard');
    }

    public function dashboard(Request $request)
    {
        $resellerId = $request->session()->get('reseller_portal_id');
        if (! $resellerId) {
            return redirect()->route('reseller-portal.index')
                ->with('info', 'Veuillez vous connecter pour accéder à votre espace.');
        }

        $reseller = Reseller::find($resellerId);
        if (! $reseller || ! $reseller->is_active) {
            $request->session()->forget(['reseller_portal_id', 'reseller_portal_name']);
            return redirect()->route('reseller-portal.index')
                ->withErrors(['phone' => 'Ce compte n\'est plus actif.']);
        }

        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->get('start_date'))->format('Y-m-d')
                : Carbon::now()->subMonths(3)->format('Y-m-d');
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->get('end_date'))->format('Y-m-d')
                : Carbon::now()->format('Y-m-d');
        } catch (\Exception) {
            $startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
            $endDate   = Carbon::now()->format('Y-m-d');
        }

        [
            'openingBalance' => $openingBalance,
            'movements'      => $movements,
            'payments'       => $payments,
            'summary'        => $summary,
        ] = $this->loyaltyService->buildStatement($reseller, $startDate, $endDate);

        // Recalculer total_payments depuis les vrais ResellerPayments actifs de la période
        $activePaymentsAmount = (float) $payments->filter(fn($p) => !$p->cancelled_at)->sum('amount');
        $summary['total_payments'] = $activePaymentsAmount;
        $summary['balance'] = max(0.0, $summary['total_purchases'] - $activePaymentsAmount);

        // Retours produits dans la période
        $productReturns = ProductReturn::where('reseller_id', $reseller->id)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->with(['product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('public.reseller-portal.dashboard', compact(
            'reseller', 'movements', 'payments', 'productReturns',
            'openingBalance', 'summary', 'startDate', 'endDate'
        ));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['reseller_portal_id', 'reseller_portal_name']);
        return redirect()->route('reseller-portal.index')
            ->with('success', 'Vous avez été déconnecté.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Services\CashierDashboardService;

/**
 * Tableau de bord Caissière - Opérations quotidiennes
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly CashierDashboardService $dashboard,
    ) {}

    public function index()
    {
        $user   = auth()->user();
        $shopId = $user->shop_id;

        $cashRegister = CashRegister::getOpenRegisterForUser($user->id);

        $stats = $this->dashboard->getStats($user);

        $readyRepairs = Repair::whereIn('status', [
            Repair::STATUS_REPAIRED,
            Repair::STATUS_READY_FOR_PICKUP,
        ])->with('customer')->get();

        $deliveredRepairs = Repair::whereIn('status', [
            Repair::STATUS_DELIVERED,
            Repair::STATUS_REPAIRED,
            Repair::STATUS_READY_FOR_PICKUP,
        ])->with(['customer', 'technician'])
            ->latest('repaired_at')->take(10)->get();

        $recentSales = Sale::where('user_id', $user->id)
            ->with(['customer', 'reseller', 'items.product'])
            ->latest()->take(5)->get();

        $resellersWithDebt = Reseller::withDebt()->orderByDesc('current_debt')->take(5)->get();

        $popularProducts = Product::withCount(['saleItems as today_sold' => function ($query) {
            $query->whereHas('sale', fn($q) => $q->today());
        }])->having('today_sold', '>', 0)->orderByDesc('today_sold')->take(5)->get();

        $recentExpenses = Expense::where('shop_id', $shopId)->with('category')->latest()->take(5)->get();

        $pendingTransfers = StockTransfer::with(['fromShop', 'items'])
            ->where('to_shop_id', $shopId)
            ->where('status', 'in_transit')
            ->latest()->get();

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

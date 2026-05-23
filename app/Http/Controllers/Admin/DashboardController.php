<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\DashboardService;
use Illuminate\Http\Request;

/**
 * Tableau de bord Admin - Vue d'ensemble en lecture seule
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(Request $request)
    {
        $shops          = Shop::active()->orderBy('name')->get();
        $selectedShopId = $request->get('shop_id') ? (int) $request->get('shop_id') : null;
        $selectedShop   = $selectedShopId ? $shops->firstWhere('id', $selectedShopId) : null;

        $stats = $this->dashboard->getStats($selectedShopId);

        [$recentSales, $recentRepairs, $lowStockProducts, $urgentSavTickets,
         $topExpenseCategories, $recentExpenses, $deliveredRepairs] = $this->dashboard->getRecentData($selectedShopId);

        [$stockValueByShop, $totalStockValue] = $this->dashboard->getStockValue($selectedShopId);

        return view('admin.dashboard', compact(
            'stats',
            'shops',
            'selectedShopId',
            'selectedShop',
            'recentSales',
            'recentRepairs',
            'lowStockProducts',
            'urgentSavTickets',
            'topExpenseCategories',
            'recentExpenses',
            'stockValueByShop',
            'totalStockValue',
            'deliveredRepairs'
        ));
    }
}

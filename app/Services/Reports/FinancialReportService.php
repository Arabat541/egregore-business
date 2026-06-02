<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\ProductReturn;
use App\Models\Repair;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SavTicket;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FinancialReportService
{
    // ──────────────────────────────────────────────────────────────
    //  Revenus
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   salesRevenue:float,
     *   repairsRevenue:float,
     *   totalRevenue:float,
     *   netRevenue:float,
     *   totalCashCollected:float,
     * }
     */
    public function getRevenue(
        string $start,
        string $end,
        ?int $shopId,
        float $savExchangeGains = 0.0,
        float $savRefunds = 0.0,
        float $savExchangeLosses = 0.0,
    ): array {
        $salesQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('is_repair_parts', false)
            ->where('payment_status', '!=', 'cancelled');
        if ($shopId) $salesQuery->where('shop_id', $shopId);

        $salesRevenue       = (float) (clone $salesQuery)->sum('total_amount');
        $totalCashCollected = (float) (clone $salesQuery)->sum('amount_paid');

        $returnsQuery = ProductReturn::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);
        if ($shopId) $returnsQuery->where('shop_id', $shopId);
        $productReturnsValue = (float) $returnsQuery->sum('total_value');
        $salesRevenue -= $productReturnsValue;

        $repairsQuery = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('status', '!=', Repair::STATUS_CANCELLED);
        if ($shopId) $repairsQuery->where('shop_id', $shopId);

        $repairsRevenue      = (float) (clone $repairsQuery)->sum('labor_cost');
        $repairsPartsRevenue = (float) (clone $repairsQuery)->sum('parts_cost');

        $salesRevenue       += $repairsPartsRevenue;
        $totalCashCollected += $repairsRevenue;

        $totalRevenue = $salesRevenue + $repairsRevenue + $savExchangeGains;
        $netRevenue   = $totalRevenue - $savRefunds - $savExchangeLosses;

        return compact('salesRevenue', 'repairsRevenue', 'totalRevenue', 'netRevenue', 'totalCashCollected');
    }

    // ──────────────────────────────────────────────────────────────
    //  Impact S.A.V
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   savRefunds:float,
     *   savExchangeLosses:float,
     *   savExchangeGains:float,
     *   savTotalImpact:float,
     *   savStats:array<string,mixed>,
     * }
     */
    public function getSavImpact(string $start, string $end, ?int $shopId): array
    {
        $savQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed']);
        if ($shopId) $savQuery->where('shop_id', $shopId);

        $savRefunds        = (float) (clone $savQuery)->sum('refund_amount');
        $savExchangeLosses = (float) abs((clone $savQuery)->where('exchange_difference', '<', 0)->sum('exchange_difference'));
        $savExchangeGains  = (float) (clone $savQuery)->where('exchange_difference', '>', 0)->sum('exchange_difference');
        $savTotalImpact    = $savRefunds + $savExchangeLosses - $savExchangeGains;

        $baseQuery = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);
        if ($shopId) $baseQuery->where('shop_id', $shopId);

        $savStats = [
            'total_tickets'   => (clone $baseQuery)->count(),
            'refunds_count'   => (clone $savQuery)->where('type', 'refund')->count(),
            'exchanges_count' => (clone $savQuery)->where('type', 'exchange')->count(),
            'returns_count'   => (clone $savQuery)->where('type', 'return')->count(),
            'total_refunds'   => $savRefunds,
            'exchange_losses' => $savExchangeLosses,
            'exchange_gains'  => $savExchangeGains,
            'net_impact'      => $savTotalImpact,
        ];

        return compact('savRefunds', 'savExchangeLosses', 'savExchangeGains', 'savTotalImpact', 'savStats');
    }

    // ──────────────────────────────────────────────────────────────
    //  Marge brute & bénéfice net
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   costOfGoodsSold:float,
     *   grossProfit:float,
     *   profitMargin:float,
     *   technicianCommission:float,
     *   netProfit:float,
     *   finalNetProfit:float,
     * }
     */
    public function getMargin(
        string $start,
        string $end,
        ?int $shopId,
        float $salesRevenue,
        float $repairsRevenue,
        float $savTotalImpact,
        float $totalExpenses,
    ): array {
        $costOfGoodsSold = (float) SaleItem::whereHas('sale', function ($q) use ($start, $end, $shopId) {
            $q->withoutGlobalScope('shop')
              ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
              ->where('is_repair_parts', false)
              ->where('payment_status', '!=', 'cancelled');
            if ($shopId) $q->where('shop_id', $shopId);
        })->join('products', 'sale_items.product_id', '=', 'products.id')
          ->sum(DB::raw('sale_items.quantity * products.purchase_price'));

        $returnedPurchaseCost = (float) ProductReturn::withoutGlobalScope('shop')
            ->whereBetween('product_returns.created_at', [$start, $end . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('product_returns.shop_id', $shopId))
            ->join('products', 'product_returns.product_id', '=', 'products.id')
            ->sum(DB::raw('product_returns.quantity * products.purchase_price'));
        $costOfGoodsSold -= $returnedPurchaseCost;

        // La main d'œuvre est partagée : le technicien perçoit technician_labor_share %
        // (les pièces de rechange sont des ventes séparées, non impactées)
        $techShare            = (int) \App\Models\Setting::get('technician_labor_share', 50, $shopId) / 100;
        $technicianCommission = round($repairsRevenue * $techShare, 2);

        $grossProfit    = $salesRevenue - $costOfGoodsSold;
        $profitMargin   = $salesRevenue > 0 ? round(($grossProfit / $salesRevenue) * 100, 1) : 0.0;
        $netProfit      = $grossProfit + $repairsRevenue - $technicianCommission - $savTotalImpact;
        $finalNetProfit = $netProfit - $totalExpenses;

        return compact('costOfGoodsSold', 'grossProfit', 'profitMargin', 'technicianCommission', 'netProfit', 'finalNetProfit');
    }

    // ──────────────────────────────────────────────────────────────
    //  Dépenses
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   totalExpenses:float,
     *   expensesByCategory:Collection,
     *   expensesByPaymentMethod:Collection,
     * }
     */
    public function getExpenses(string $start, string $end, ?int $shopId): array
    {
        $query = Expense::whereBetween('expense_date', [$start, $end])
            ->where('status', 'approved');
        if ($shopId) $query->where('shop_id', $shopId);

        $totalExpenses = (float) (clone $query)->sum('amount');

        $expensesByCategory = (clone $query)
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        $expensesByPaymentMethod = (clone $query)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        return compact('totalExpenses', 'expensesByCategory', 'expensesByPaymentMethod');
    }

    // ──────────────────────────────────────────────────────────────
    //  Créances
    // ──────────────────────────────────────────────────────────────

    /** @return array{salesCredit:float,resellerDebt:float} */
    public function getCredit(string $start, string $end, ?int $shopId): array
    {
        $creditQuery = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('payment_status', 'credit');
        if ($shopId) $creditQuery->where('shop_id', $shopId);

        $salesCredit = (float) $creditQuery->sum(DB::raw('total_amount - amount_paid'));

        $resellerQuery = Reseller::query();
        if ($shopId) {
            $resellerQuery->whereHas('sales', fn($q) => $q->withoutGlobalScope('shop')->where('shop_id', $shopId));
        }

        $resellerDebt = (float) $resellerQuery->sum('current_debt');

        return compact('salesCredit', 'resellerDebt');
    }

    // ──────────────────────────────────────────────────────────────
    //  Flux de caisse
    // ──────────────────────────────────────────────────────────────

    /** @return array{cashIn:float,cashOut:float} */
    public function getCashFlow(string $start, string $end, ?int $shopId): array
    {
        $inQuery  = CashTransaction::whereBetween('created_at', [$start, $end . ' 23:59:59'])->where('type', CashTransaction::TYPE_INCOME);
        $outQuery = CashTransaction::whereBetween('created_at', [$start, $end . ' 23:59:59'])->where('type', CashTransaction::TYPE_EXPENSE);

        if ($shopId) {
            $inQuery->whereHas('cashRegister', fn($q) => $q->where('shop_id', $shopId));
            $outQuery->whereHas('cashRegister', fn($q) => $q->where('shop_id', $shopId));
        }

        return [
            'cashIn'  => (float) $inQuery->sum('amount'),
            'cashOut' => (float) $outQuery->sum('amount'),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Évolution journalière (ventes + réparations + SAV + dépenses)
    // ──────────────────────────────────────────────────────────────

    public function getDailyBreakdown(string $start, string $end, ?int $shopId): Collection
    {
        $revenueByDay = DB::table('sales')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('is_repair_parts', false)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as sales'))
            ->groupBy('date')->get()->keyBy('date');

        $repairsPartsByDay = DB::table('repairs')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(parts_cost) as parts'))
            ->groupBy('date')->get()->keyBy('date');

        $repairsByDay = DB::table('repairs')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(labor_cost) as repairs'))
            ->groupBy('date')->get()->keyBy('date');

        $savByDay = DB::table('sav_tickets')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed'])
            ->whereNull('deleted_at')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as refunds'),
                DB::raw('SUM(CASE WHEN exchange_difference < 0 THEN ABS(exchange_difference) ELSE 0 END) as exchange_losses'),
            )
            ->groupBy('date')->get()->keyBy('date');

        $expensesByDay = DB::table('expenses')
            ->whereBetween('expense_date', [$start, $end])
            ->where('status', 'approved')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select(DB::raw('DATE(expense_date) as date'), DB::raw('SUM(amount) as expenses'))
            ->groupBy('date')->get()->keyBy('date');

        $returnsByDay = DB::table('product_returns')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_value) as returns'))
            ->groupBy('date')->get()->keyBy('date');

        $dates      = collect();
        $current    = Carbon::parse($start);
        $endCarbon  = Carbon::parse($end);

        while ($current <= $endCarbon) {
            $d      = $current->format('Y-m-d');
            $savRow = $savByDay->get($d);

            $salesTotal  = ($revenueByDay->get($d)->sales ?? 0)
                         + ($repairsPartsByDay->get($d)->parts ?? 0)
                         - ($returnsByDay->get($d)->returns ?? 0);
            $repairsTotal = $repairsByDay->get($d)->repairs ?? 0;
            $savLosses    = $savRow ? ($savRow->refunds + $savRow->exchange_losses) : 0;
            $expenses     = $expensesByDay->get($d)->expenses ?? 0;

            $dates->push([
                'date'       => $d,
                'sales'      => $salesTotal,
                'repairs'    => $repairsTotal,
                'sav_losses' => $savLosses,
                'expenses'   => $expenses,
                'net'        => $salesTotal + $repairsTotal - $savLosses - $expenses,
            ]);

            $current->addDay();
        }

        return $dates;
    }

    // ──────────────────────────────────────────────────────────────
    //  Répartition par mode de paiement
    // ──────────────────────────────────────────────────────────────

    public function getByPayment(string $start, string $end, ?int $shopId): Collection
    {
        return Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->where('is_repair_parts', false)
            ->where('payment_status', '!=', 'cancelled')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select('payment_method', DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Historique des caisses
    // ──────────────────────────────────────────────────────────────

    public function getCashRegisters(string $start, string $end, ?int $shopId): Collection
    {
        $query = CashRegister::withoutGlobalScope('shop')
            ->whereBetween('opened_at', [$start, $end . ' 23:59:59'])
            ->with(['user', 'transactions']);
        if ($shopId) $query->where('shop_id', $shopId);

        return $query->orderByDesc('opened_at')->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\User;

final class CashierDashboardService
{
    /**
     * Retourne les KPIs du jour pour la caissière connectée.
     *
     * @return array<string,mixed>
     */
    public function getStats(User $user): array
    {
        $todaySales = Sale::where('user_id', $user->id)->today()
            ->where('payment_status', '!=', 'cancelled')->get();

        $stats = [
            'today_sales_count'          => $todaySales->count(),
            'today_sales_amount'         => (float) $todaySales->sum('total_amount'),
            'today_cash_sales'           => (float) $todaySales->where('payment_method', 'cash')->sum('total_amount'),
            'today_mobile_money_sales'   => (float) $todaySales->where('payment_method', 'mobile_money')->sum('total_amount'),
            'today_card_sales'           => (float) $todaySales->where('payment_method', 'card')->sum('total_amount'),
            'today_credit_sales'         => (float) $todaySales->where('payment_status', 'credit')->sum('total_amount'),
        ];

        $expenseQuery = Expense::where('shop_id', $user->shop_id)->today();
        $stats['today_expenses_count']   = (clone $expenseQuery)->approved()->count();
        $stats['today_expenses_amount']  = (float) (clone $expenseQuery)->approved()->sum('amount');
        $stats['today_pending_expenses'] = (clone $expenseQuery)->pending()->count();

        $todayRepairs = Repair::where('created_by', $user->id)->today()->get();
        $stats['today_repairs_count']       = $todayRepairs->count();
        $stats['today_repairs_labor']       = (float) $todayRepairs->sum('labor_cost');
        $stats['today_repairs_parts']       = (float) $todayRepairs->sum('parts_cost');
        $stats['today_repairs_with_parts']  = $todayRepairs->where('parts_cost', '>', 0)->count();

        // Pièces → CA ventes ; main d'œuvre → CA réparations
        $stats['today_repairs_amount'] = $stats['today_repairs_labor'];
        $stats['today_sales_amount']  += $stats['today_repairs_parts'];
        $stats['today_total_ca']       = $stats['today_sales_amount'] + $stats['today_repairs_amount'];

        return $stats;
    }
}

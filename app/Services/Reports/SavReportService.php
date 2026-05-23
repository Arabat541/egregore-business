<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Sale;
use App\Models\SavTicket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SavReportService
{
    // ──────────────────────────────────────────────────────────────
    //  Query builder partagé (avec filtres type + clientType)
    // ──────────────────────────────────────────────────────────────

    private function baseQuery(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Builder {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);

        if ($shopId)  $q->where('shop_id', $shopId);
        if ($savType) $q->where('type', $savType);

        if ($customerType === 'reseller') {
            $q->whereHas('sale', fn($sq) => $sq->where('client_type', 'reseller'));
        } elseif ($customerType === 'customer') {
            $q->where(function ($sq) {
                $sq->whereNull('sale_id')
                   ->orWhereHas('sale', fn($r) => $r->whereIn('client_type', ['customer', 'walk-in']));
            });
        }

        return $q;
    }

    // ──────────────────────────────────────────────────────────────
    //  KPIs généraux
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{totalTickets:int,openTickets:int,closedTickets:int}
     */
    public function getKpis(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): array {
        $q = $this->baseQuery($start, $end, $shopId, $savType, $customerType);

        return [
            'totalTickets'  => (clone $q)->count(),
            'openTickets'   => (clone $q)->open()->count(),
            'closedTickets' => (clone $q)->closed()->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Impact financier
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   totalRefunds:float,
     *   totalExchangeLosses:float,
     *   totalExchangeGains:float,
     * }
     */
    public function getFinancialImpact(string $start, string $end, ?int $shopId): array
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereIn('status', ['resolved', 'closed']);
        if ($shopId) $q->where('shop_id', $shopId);

        $totalRefunds        = (float) (clone $q)->sum('refund_amount');
        $totalExchangeLosses = (float) (clone $q)->where('exchange_difference', '<', 0)
            ->sum(DB::raw('ABS(exchange_difference)'));
        $totalExchangeGains  = (float) (clone $q)->where('exchange_difference', '>', 0)
            ->sum('exchange_difference');

        return compact('totalRefunds', 'totalExchangeLosses', 'totalExchangeGains');
    }

    // ──────────────────────────────────────────────────────────────
    //  Répartitions
    // ──────────────────────────────────────────────────────────────

    public function getByType(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Collection {
        return $this->baseQuery($start, $end, $shopId, $savType, $customerType)
            ->select(
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds'),
                DB::raw('SUM(CASE WHEN exchange_difference < 0 THEN ABS(exchange_difference) ELSE 0 END) as exchange_losses'),
            )
            ->groupBy('type')
            ->get();
    }

    public function getByStatus(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Collection {
        return $this->baseQuery($start, $end, $shopId, $savType, $customerType)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
    }

    public function getByPriority(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Collection {
        return $this->baseQuery($start, $end, $shopId, $savType, $customerType)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();
    }

    public function getByDay(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Collection {
        return $this->baseQuery($start, $end, $shopId, $savType, $customerType)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as refunds'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Indicateurs anti-malversation
    // ──────────────────────────────────────────────────────────────

    public function getByCreator(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Collection {
        return $this->baseQuery($start, $end, $shopId, $savType, $customerType)
            ->with('creator')
            ->select(
                'created_by',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(CASE WHEN type = "refund" THEN 1 ELSE 0 END) as refund_count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds'),
                DB::raw('SUM(CASE WHEN exchange_difference < 0 THEN ABS(exchange_difference) ELSE 0 END) as exchange_losses'),
            )
            ->groupBy('created_by')
            ->orderByDesc('total_refunds')
            ->get();
    }

    public function getSalesWithMostSav(string $start, string $end, ?int $shopId, int $limit = 20): Collection
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('sav_tickets.created_at', [$start, $end . ' 23:59:59'])
            ->whereNotNull('sale_id');
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->with(['sale.user', 'sale.customer', 'sale.reseller'])
            ->select(
                'sale_id',
                DB::raw('COUNT(*) as sav_count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds'),
            )
            ->groupBy('sale_id')
            ->having('sav_count', '>=', 1)
            ->orderByDesc('sav_count')
            ->limit($limit)
            ->get();
    }

    public function getProblematicProducts(string $start, string $end, ?int $shopId, int $limit = 15): Collection
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereNotNull('product_id');
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->with('product')
            ->select(
                'product_id',
                DB::raw('COUNT(*) as sav_count'),
                DB::raw('SUM(CASE WHEN type = "return" THEN 1 ELSE 0 END) as return_count'),
                DB::raw('SUM(CASE WHEN type = "exchange" THEN 1 ELSE 0 END) as exchange_count'),
                DB::raw('SUM(CASE WHEN type = "refund" THEN 1 ELSE 0 END) as refund_count'),
                DB::raw('SUM(CASE WHEN type = "warranty" THEN 1 ELSE 0 END) as warranty_count'),
            )
            ->groupBy('product_id')
            ->orderByDesc('sav_count')
            ->limit($limit)
            ->get();
    }

    public function getCustomersWithMostSav(string $start, string $end, ?int $shopId, int $limit = 15): Collection
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereNotNull('customer_id');
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->with('customer')
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as sav_count'),
                DB::raw('SUM(COALESCE(refund_amount, 0)) as total_refunds'),
            )
            ->groupBy('customer_id')
            ->having('sav_count', '>=', 2)
            ->orderByDesc('sav_count')
            ->limit($limit)
            ->get();
    }

    public function getSuspiciousRefunds(string $start, string $end, ?int $shopId): Collection
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('sav_tickets.created_at', [$start, $end . ' 23:59:59'])
            ->where('type', 'refund')
            ->whereIn('status', ['resolved', 'closed'])
            ->where('refund_amount', '>', 0);
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->with(['sale', 'creator', 'customer'])
            ->get()
            ->filter(function ($ticket) {
                $alerts = [];

                if ($ticket->refund_amount > 50000) {
                    $alerts[] = 'montant_eleve';
                }
                if ($ticket->sale && $ticket->sale->user_id === $ticket->created_by) {
                    $alerts[] = 'meme_employe';
                }
                if ($ticket->sale && $ticket->created_at->diffInHours($ticket->sale->created_at) < 24) {
                    $alerts[] = 'rapide';
                }
                if (!$ticket->sale_id) {
                    $alerts[] = 'sans_vente';
                }

                $ticket->alerts = $alerts;
                return count($alerts) > 0;
            })
            ->values();
    }

    public function getSavByVendor(string $start, string $end, ?int $shopId): Collection
    {
        $salesCount = Sale::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->select('user_id', DB::raw('COUNT(*) as sales_count'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('sav_tickets.created_at', [$start, $end . ' 23:59:59'])
            ->whereNotNull('sale_id');
        if ($shopId) $q->where('sav_tickets.shop_id', $shopId);

        return $q->join('sales', 'sav_tickets.sale_id', '=', 'sales.id')
            ->with('sale.user')
            ->select('sales.user_id', DB::raw('COUNT(DISTINCT sav_tickets.id) as sav_count'))
            ->groupBy('sales.user_id')
            ->get()
            ->map(function ($item) use ($salesCount) {
                $totalSales        = $salesCount->get($item->user_id)->sales_count ?? 0;
                $item->total_sales = $totalSales;
                $item->sav_rate    = $totalSales > 0 ? round(($item->sav_count / $totalSales) * 100, 2) : 0;
                return $item;
            })
            ->sortByDesc('sav_rate')
            ->values();
    }

    // ──────────────────────────────────────────────────────────────
    //  Indicateurs temporels
    // ──────────────────────────────────────────────────────────────

    public function getAvgResolutionTime(string $start, string $end, ?int $shopId): ?float
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereNotNull('resolved_at');
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');
    }

    public function getOldOpenTickets(?int $shopId, int $daysOld = 7): Collection
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->where('created_at', '<', Carbon::now()->subDays($daysOld))
            ->open();
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->with(['customer', 'creator', 'assignedUser'])
            ->orderBy('created_at')
            ->get();
    }

    public function getRecentRefunds(string $start, string $end, ?int $shopId, int $limit = 30): Collection
    {
        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59'])
            ->whereIn('type', ['refund', 'return'])
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function ($sq) {
                $sq->where('refund_amount', '>', 0)
                   ->orWhere('exchange_difference', '<', 0);
            });
        if ($shopId) $q->where('shop_id', $shopId);

        return $q->with(['customer', 'sale', 'product', 'creator', 'assignedUser'])
            ->orderByDesc('resolved_at')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Comparaison période précédente
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   previousTotalTickets:int,
     *   previousRefunds:float,
     *   ticketGrowth:float,
     *   refundGrowth:float,
     * }
     */
    public function getPreviousPeriod(
        string $start,
        string $end,
        ?int $shopId,
        int $totalTickets,
        float $totalRefunds,
    ): array {
        $daysDiff      = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
        $previousStart = Carbon::parse($start)->subDays($daysDiff)->format('Y-m-d');
        $previousEnd   = Carbon::parse($start)->subDay()->format('Y-m-d');

        $q = SavTicket::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$previousStart, $previousEnd . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $previousTotalTickets = (clone $q)->count();
        $previousRefunds      = (float) (clone $q)->sum('refund_amount');

        $ticketGrowth = $previousTotalTickets > 0
            ? round((($totalTickets - $previousTotalTickets) / $previousTotalTickets) * 100, 1)
            : 0.0;
        $refundGrowth = $previousRefunds > 0
            ? round((($totalRefunds - $previousRefunds) / $previousRefunds) * 100, 1)
            : 0.0;

        return compact('previousTotalTickets', 'previousRefunds', 'ticketGrowth', 'refundGrowth');
    }

    // ──────────────────────────────────────────────────────────────
    //  Données PDF (version compacte sans indicateurs anti-malversation)
    // ──────────────────────────────────────────────────────────────

    public function getByCreatorPdf(
        string $start,
        string $end,
        ?int $shopId,
        ?string $savType = null,
        ?string $customerType = null,
    ): Collection {
        return $this->baseQuery($start, $end, $shopId, $savType, $customerType)
            ->with('creator')
            ->select(
                'created_by',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(COALESCE(refund_amount,0)) as total_refunds'),
            )
            ->groupBy('created_by')
            ->orderByDesc('total_refunds')
            ->get();
    }
}

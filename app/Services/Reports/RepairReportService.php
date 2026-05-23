<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Repair;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RepairReportService
{
    // ──────────────────────────────────────────────────────────────
    //  Query builder partagé
    // ──────────────────────────────────────────────────────────────

    private function baseQuery(string $start, string $end, ?int $shopId): \Illuminate\Database\Eloquent\Builder
    {
        $q = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$start, $end . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);
        return $q;
    }

    // ──────────────────────────────────────────────────────────────
    //  KPIs
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   totalRepairs:int,
     *   totalRevenue:float,
     *   averageRepairTime:float|null,
     *   deliveredCount:int,
     *   successRate:float,
     * }
     */
    public function getKpis(string $start, string $end, ?int $shopId): array
    {
        $q = $this->baseQuery($start, $end, $shopId);

        $totalRepairs    = (clone $q)->count();
        $totalRevenue    = (float) (clone $q)->sum('labor_cost');
        $deliveredCount  = (clone $q)->where('status', 'delivered')->count();
        $successRate     = $totalRepairs > 0 ? round(($deliveredCount / $totalRepairs) * 100, 1) : 0.0;
        $averageRepairTime = (clone $q)->whereNotNull('repaired_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, repaired_at)) as avg_hours')
            ->value('avg_hours');

        return compact('totalRepairs', 'totalRevenue', 'averageRepairTime', 'deliveredCount', 'successRate');
    }

    // ──────────────────────────────────────────────────────────────
    //  Répartitions
    // ──────────────────────────────────────────────────────────────

    public function getByStatus(string $start, string $end, ?int $shopId): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
    }

    public function getByDay(string $start, string $end, ?int $shopId): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(labor_cost) as total'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getByDevice(string $start, string $end, ?int $shopId): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->select('device_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(labor_cost) as total'))
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get();
    }

    public function getByBrand(string $start, string $end, ?int $shopId, int $limit = 10): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->whereNotNull('device_brand')
            ->select('device_brand', DB::raw('COUNT(*) as count'))
            ->groupBy('device_brand')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Performance des techniciens
    // ──────────────────────────────────────────────────────────────

    public function getTechnicianPerformance(string $start, string $end, ?int $shopId): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->whereNotNull('technician_id')
            ->with('technician')
            ->select(
                'technician_id',
                DB::raw('COUNT(*) as total_repairs'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed'),
                DB::raw('AVG(CASE WHEN repaired_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, diagnosis_at, repaired_at) END) as avg_repair_hours'),
            )
            ->groupBy('technician_id')
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Problèmes fréquents
    // ──────────────────────────────────────────────────────────────

    public function getCommonIssues(string $start, string $end, ?int $shopId, int $limit = 10): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->select('reported_issue', DB::raw('COUNT(*) as count'))
            ->groupBy('reported_issue')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    //  Comparaison N-1
    // ──────────────────────────────────────────────────────────────

    /** @return array{n1Repairs:int,n1Revenue:float,n1RepGrowth:float|null} */
    public function getN1(string $start, string $end, ?int $shopId, float $totalRevenue): array
    {
        $n1Start = Carbon::parse($start)->subYear()->format('Y-m-d');
        $n1End   = Carbon::parse($end)->subYear()->format('Y-m-d');

        $q = Repair::withoutGlobalScope('shop')
            ->whereBetween('created_at', [$n1Start, $n1End . ' 23:59:59']);
        if ($shopId) $q->where('shop_id', $shopId);

        $n1Repairs   = (clone $q)->count();
        $n1Revenue   = (float) (clone $q)->sum('labor_cost');
        $n1RepGrowth = $n1Revenue > 0
            ? round((($totalRevenue - $n1Revenue) / $n1Revenue) * 100, 1)
            : null;

        return compact('n1Repairs', 'n1Revenue', 'n1RepGrowth');
    }

    // ──────────────────────────────────────────────────────────────
    //  Données pour le PDF (requêtes communes mais légèrement différentes)
    // ──────────────────────────────────────────────────────────────

    public function getTechnicianPerformancePdf(string $start, string $end, ?int $shopId): Collection
    {
        return $this->baseQuery($start, $end, $shopId)
            ->whereNotNull('technician_id')
            ->with('technician')
            ->select(
                'technician_id',
                DB::raw('COUNT(*) as total_repairs'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(labor_cost) as total_revenue'),
            )
            ->groupBy('technician_id')
            ->get();
    }
}

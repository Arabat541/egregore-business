<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réparations — lecture seule pour l'API mobile
 * Les techniciens peuvent mettre à jour le statut
 */
class RepairController extends Controller
{
    /**
     * GET /api/repairs?status=&search=&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Repair::with(['customer', 'technician']);

        // Les techniciens ne voient que leurs réparations
        if ($user->hasRole('technicien')) {
            $query->where('technician_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $perPage  = min((int) $request->input('per_page', 20), 100);
        $repairs  = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $repairs->map(fn(Repair $r) => $this->formatRepair($r)),
            'meta' => [
                'total'        => $repairs->total(),
                'per_page'     => $repairs->perPage(),
                'current_page' => $repairs->currentPage(),
                'last_page'    => $repairs->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/repairs/{repair}
     */
    public function show(Repair $repair): JsonResponse
    {
        $repair->load(['customer', 'technician', 'parts.product']);
        return response()->json($this->formatRepair($repair, detailed: true));
    }

    /**
     * PATCH /api/repairs/{repair}/status
     * Body: { "status": "in_repair" }
     * Accessible aux techniciens et caissières uniquement
     */
    public function updateStatus(Request $request, Repair $repair): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['technicien', 'caissiere', 'admin'])) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $request->validate([
            'status' => 'required|in:in_diagnosis,waiting_parts,in_repair,repaired,unrepairable,ready_for_pickup',
        ]);

        $repair->updateStatus($request->status);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'repair'  => $this->formatRepair($repair->fresh()),
        ]);
    }

    private function formatRepair(Repair $r, bool $detailed = false): array
    {
        $base = [
            'id'             => $r->id,
            'repair_number'  => $r->repair_number,
            'status'         => $r->status,
            'device_type'    => $r->device_type,
            'device_brand'   => $r->device_brand,
            'device_model'   => $r->device_model,
            'customer'       => $r->customer ? [
                'id'    => $r->customer->id,
                'name'  => $r->customer->full_name,
                'phone' => $r->customer->phone,
            ] : null,
            'technician'     => $r->technician ? [
                'id'   => $r->technician->id,
                'name' => $r->technician->name,
            ] : null,
            'final_cost'     => (float) ($r->final_cost ?? 0),
            'amount_paid'    => (float) ($r->amount_paid ?? 0),
            'created_at'     => $r->created_at->toIso8601String(),
            'estimated_completion_date' => $r->estimated_completion_date?->toDateString(),
        ];

        if ($detailed) {
            $base['problem_description'] = $r->problem_description;
            $base['diagnosis']           = $r->diagnosis;
            $base['parts'] = ($r->parts ?? collect())->map(fn($part) => [
                'product' => $part->product?->name,
                'quantity' => $part->quantity,
                'unit_price' => (float) $part->unit_price,
            ]);
        }

        return $base;
    }
}

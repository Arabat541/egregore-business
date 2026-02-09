<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\Request;

/**
 * Tableau de bord Technicien - Réparations assignées
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Réparations assignées au technicien
        $myRepairs = Repair::forTechnician($user->id)
            ->pending()
            ->with('customer')
            ->orderByRaw("FIELD(status, 'paid_pending_diagnosis', 'in_diagnosis', 'waiting_parts', 'in_repair', 'repaired', 'ready_for_pickup')")
            ->get();

        // Réparations non assignées (disponibles)
        $unassignedRepairs = Repair::unassigned()
            ->whereIn('status', [
                Repair::STATUS_PAID_PENDING_DIAGNOSIS,
            ])
            ->with('customer')
            ->latest()
            ->get();

        // Statistiques
        $stats = [
            'assigned_count' => $myRepairs->count(),
            'in_diagnosis' => $myRepairs->where('status', Repair::STATUS_IN_DIAGNOSIS)->count(),
            'in_repair' => $myRepairs->where('status', Repair::STATUS_IN_REPAIR)->count(),
            'waiting_parts' => $myRepairs->where('status', Repair::STATUS_WAITING_PARTS)->count(),
            'repaired_today' => Repair::forTechnician($user->id)
                ->whereDate('repaired_at', today())
                ->count(),
        ];

        // Grouper par statut pour affichage
        $repairsByStatus = $myRepairs->groupBy('status');

        return view('technician.dashboard', compact(
            'myRepairs',
            'unassignedRepairs',
            'stats',
            'repairsByStatus'
        ));
    }
}

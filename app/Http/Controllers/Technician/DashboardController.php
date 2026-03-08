<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use App\Models\SavReplacedPart;
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

        // CA brut du jour (main d'œuvre uniquement)
        $caDayBrut = Repair::forTechnician($user->id)
            ->whereIn('status', [Repair::STATUS_DELIVERED, Repair::STATUS_READY_FOR_PICKUP, Repair::STATUS_REPAIRED])
            ->whereDate('repaired_at', today())
            ->sum('labor_cost');
        
        // Déductions SAV du jour (pièces défectueuses remplacées)
        $savDeductionDay = SavReplacedPart::forTechnicianToday($user->id)->sum('defective_part_cost');
        
        // CA brut du mois (main d'œuvre uniquement)
        $caMonthBrut = Repair::forTechnician($user->id)
            ->whereIn('status', [Repair::STATUS_DELIVERED, Repair::STATUS_READY_FOR_PICKUP, Repair::STATUS_REPAIRED])
            ->whereMonth('repaired_at', now()->month)
            ->whereYear('repaired_at', now()->year)
            ->sum('labor_cost');
        
        // Déductions SAV du mois (pièces défectueuses remplacées)
        $savDeductionMonth = SavReplacedPart::forTechnicianMonth($user->id)->sum('defective_part_cost');

        // Statistiques
        $stats = [
            'assigned_count' => $myRepairs->count(),
            'in_diagnosis' => $myRepairs->where('status', Repair::STATUS_IN_DIAGNOSIS)->count(),
            'in_repair' => $myRepairs->where('status', Repair::STATUS_IN_REPAIR)->count(),
            'waiting_parts' => $myRepairs->where('status', Repair::STATUS_WAITING_PARTS)->count(),
            'repaired_today' => Repair::forTechnician($user->id)
                ->whereDate('repaired_at', today())
                ->count(),
            // CA net du jour (brut - déductions SAV)
            'ca_day' => max(0, $caDayBrut - $savDeductionDay),
            'ca_day_brut' => $caDayBrut,
            'sav_deduction_day' => $savDeductionDay,
            // CA net du mois (brut - déductions SAV)
            'ca_month' => max(0, $caMonthBrut - $savDeductionMonth),
            'ca_month_brut' => $caMonthBrut,
            'sav_deduction_month' => $savDeductionMonth,
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

    /**
     * Affiche les déductions SAV du technicien
     */
    public function savDeductions()
    {
        $user = auth()->user();

        // Récupérer toutes les pièces remplacées pour ce technicien
        $savDeductions = SavReplacedPart::where('technician_id', $user->id)
            ->with([
                'savTicket.repair.customer',
                'defectiveProduct',
                'replacementProduct',
                'deductedBy'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistiques
        $stats = [
            'total_deductions' => SavReplacedPart::where('technician_id', $user->id)->sum('defective_part_cost'),
            'deductions_this_month' => SavReplacedPart::forTechnicianMonth($user->id)->sum('defective_part_cost'),
            'deductions_today' => SavReplacedPart::forTechnicianToday($user->id)->sum('defective_part_cost'),
            'total_count' => SavReplacedPart::where('technician_id', $user->id)->count(),
        ];

        return view('technician.sav-deductions', compact('savDeductions', 'stats'));
    }
}

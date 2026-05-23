<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Models\Repair;
use App\Models\RepairPart;
use App\Services\NotificationService;
use App\Services\RepairService;
use Illuminate\Http\Request;

/**
 * Gestion des réparations - Technicien
 * Diagnostic, réparation, mise à jour des statuts
 */
class RepairController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly RepairService $repairService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Repair::forTechnician($user->id)->with('customer');

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        } else {
            $query->pending();
        }

        $repairs = $query->latest()->paginate(20);
        $statuses = Repair::getStatuses();

        return view('technician.repairs.index', compact('repairs', 'statuses'));
    }

    public function show(Repair $repair)
    {
        // Vérifier que la réparation est assignée au technicien
        $this->authorizeRepair($repair);

        $repair->load(['customer', 'creator', 'parts.product.category']);

        // Pièces détachées disponibles
        $products = Product::spareParts()
            ->active()
            ->inStock()
            ->orderBy('name')
            ->get();

        return view('technician.repairs.show', compact('repair', 'products'));
    }

    /**
     * Prendre en charge une réparation non assignée
     */
    public function takeOver(Repair $repair)
    {
        if ($repair->technician_id !== null) {
            return back()->with('error', 'Cette réparation est déjà assignée.');
        }

        if ($repair->status !== Repair::STATUS_PAID_PENDING_DIAGNOSIS) {
            return back()->with('error', 'Cette réparation n\'est pas disponible.');
        }

        $repair->assignTechnician(auth()->id());

        ActivityLog::log('update', $repair, null, ['technician_id' => auth()->id()], "Prise en charge réparation #{$repair->repair_number}");

        return redirect()->route('technician.repairs.show', $repair)
            ->with('success', 'Réparation prise en charge.');
    }

    /**
     * Formulaire de diagnostic
     */
    public function diagnosisForm(Repair $repair)
    {
        $this->authorizeRepair($repair);

        if (!$repair->canBeDiagnosed()) {
            return back()->with('error', 'Cette réparation ne peut pas être diagnostiquée.');
        }

        return view('technician.repairs.diagnosis', compact('repair'));
    }

    /**
     * Enregistrer le diagnostic
     */
    public function storeDiagnosis(Request $request, Repair $repair)
    {
        $this->authorizeRepair($repair);

        if (!$repair->canBeDiagnosed()) {
            return back()->with('error', 'Cette réparation ne peut pas être diagnostiquée.');
        }

        $validated = $request->validate([
            'diagnosis' => 'required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:in_diagnosis,waiting_parts,in_repair',
        ]);

        $oldValues = $repair->only(['diagnosis', 'estimated_cost', 'status']);

        $repair->update([
            'diagnosis' => $validated['diagnosis'],
            'estimated_cost' => $validated['estimated_cost'] ?? $repair->estimated_cost,
            'status' => $validated['status'],
            'diagnosis_at' => now(),
        ]);

        ActivityLog::log('update', $repair, $oldValues, $repair->only(['diagnosis', 'estimated_cost', 'status']), "Diagnostic réparation #{$repair->repair_number}");

        return redirect()->route('technician.repairs.show', $repair)
            ->with('success', 'Diagnostic enregistré.');
    }

    /**
     * Mettre à jour le statut
     */
    public function updateStatus(Request $request, Repair $repair)
    {
        $this->authorizeRepair($repair);

        $validated = $request->validate([
            'status' => 'required|in:in_diagnosis,waiting_parts,in_repair,repaired,ready_for_pickup,unrepairable',
            'repair_notes' => 'nullable|string',
        ]);

        $oldStatus = $repair->status;

        $updateData = ['status' => $validated['status']];

        if (!empty($validated['repair_notes'])) {
            $updateData['repair_notes'] = $validated['repair_notes'];
        }

        if ($validated['status'] === Repair::STATUS_REPAIRED) {
            $updateData['repaired_at'] = now();
        }

        $repair->update($updateData);

        // Notifier les caissières selon le statut final
        if (in_array($validated['status'], [Repair::STATUS_REPAIRED, Repair::STATUS_READY_FOR_PICKUP])) {
            $this->notificationService->repairReady($repair);
        } elseif ($validated['status'] === Repair::STATUS_UNREPAIRABLE) {
            $this->notificationService->repairUnrepairable($repair);
        }

        ActivityLog::log('update', $repair, ['status' => $oldStatus], ['status' => $validated['status']], "Changement statut réparation #{$repair->repair_number}");

        return back()->with('success', 'Statut mis à jour.');
    }

    /**
     * Mettre à jour les informations de travail (diagnostic, travaux, coût)
     */
    public function update(Request $request, Repair $repair)
    {
        $this->authorizeRepair($repair);

        $validated = $request->validate([
            'diagnosis' => 'nullable|string',
            'work_done' => 'nullable|string',
            'labor_cost' => 'nullable|numeric|min:0',
            'repair_notes' => 'nullable|string',
        ]);

        $oldValues = $repair->only(['diagnosis', 'work_done', 'labor_cost', 'repair_notes']);

        $repair->update($validated);

        ActivityLog::log('update', $repair, $oldValues, $validated, "Mise à jour travail réparation #{$repair->repair_number}");

        return back()->with('success', 'Informations mises à jour.');
    }

    /**
     * Ajouter une pièce utilisée - Crée automatiquement une vente pour la comptabilité
     */
    public function addPart(Request $request, Repair $repair)
    {
        $this->authorizeRepair($repair);

        if (!$repair->canAddParts()) {
            return back()->with('error', 'Impossible d\'ajouter des pièces à cette réparation.');
        }

        $validated = $request->validate([
            'product_id'  => 'nullable|exists:products,id',
            'description' => 'nullable|string|max:255',
            'quantity'    => 'required|integer|min:1',
            'unit_price'  => 'required|numeric|min:0',
        ]);

        if (empty($validated['product_id']) && empty($validated['description'])) {
            return back()->with('error', 'Veuillez sélectionner un produit ou saisir une description.');
        }

        try {
            $part = $this->repairService->addPart($repair, $validated, auth()->id());

            return back()->with('success', "Pièce ajoutée: {$validated['quantity']}x {$part->description}");

        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur'));
        }
    }

    /**
     * Supprimer une pièce - Annule aussi la vente associée
     */
    public function removePart(Repair $repair, RepairPart $part)
    {
        $this->authorizeRepair($repair);

        if ($part->repair_id !== $repair->id) {
            abort(404);
        }

        try {
            $this->repairService->removePart($repair, $part);

            return back()->with('success', 'Pièce retirée et vente annulée.');

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur'));
        }
    }

    /**
     * Marquer comme réparé
     */
    public function markAsRepaired(Request $request, Repair $repair)
    {
        $this->authorizeRepair($repair);

        $validated = $request->validate([
            'final_cost' => 'required|numeric|min:0',
            'repair_notes' => 'nullable|string',
        ]);

        $repair->update([
            'status' => Repair::STATUS_REPAIRED,
            'final_cost' => $validated['final_cost'],
            'repair_notes' => $validated['repair_notes'] ?? $repair->repair_notes,
            'repaired_at' => now(),
        ]);

        ActivityLog::log('update', $repair, null, ['status' => 'repaired', 'final_cost' => $validated['final_cost']], "Réparation terminée #{$repair->repair_number}");

        return redirect()->route('technician.repairs.show', $repair)
            ->with('success', 'Réparation marquée comme terminée.');
    }

    /**
     * Vérifier que le technicien a accès à cette réparation
     */
    protected function authorizeRepair(Repair $repair): void
    {
        if ($repair->technician_id !== auth()->id()) {
            Log::warning('Unauthorized repair access attempt', [
                'repair_id'     => $repair->id,
                'repair_number' => $repair->repair_number,
                'owner_id'      => $repair->technician_id,
                'attacker_id'   => auth()->id(),
            ]);
            abort(403, 'Cette réparation n\'est pas assignée à vous.');
        }
    }
}

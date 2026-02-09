<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Repair;
use App\Models\RepairPart;
use App\Models\StockMovement;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des réparations - Technicien
 * Diagnostic, réparation, mise à jour des statuts
 */
class RepairController extends Controller
{
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

        $repair->load(['customer', 'creator', 'parts.product']);

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

        // Notifier les caissières si la réparation est terminée
        if (in_array($validated['status'], [Repair::STATUS_REPAIRED, Repair::STATUS_READY_FOR_PICKUP])) {
            app(NotificationService::class)->repairReady($repair);
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

        // Permettre l'ajout de pièces pendant diagnostic et réparation
        if (!$repair->canAddParts()) {
            return back()->with('error', 'Impossible d\'ajouter des pièces à cette réparation.');
        }

        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'description' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        // Vérifier qu'on a soit un produit soit une description
        if (empty($validated['product_id']) && empty($validated['description'])) {
            return back()->with('error', 'Veuillez sélectionner un produit ou saisir une description.');
        }

        DB::beginTransaction();
        try {
            $partData = [
                'repair_id' => $repair->id,
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_price'],
                'total_cost' => $validated['unit_price'] * $validated['quantity'],
            ];

            $product = null;
            if (!empty($validated['product_id'])) {
                $product = Product::findOrFail($validated['product_id']);

                // Vérifier le stock
                if (!$product->hasStock($validated['quantity'])) {
                    return back()->with('error', "Stock insuffisant pour {$product->name}.");
                }

                $partData['product_id'] = $product->id;
                $partData['description'] = $product->name;
                $partName = $product->name;
            } else {
                // Pièce manuelle (non en stock)
                $partData['description'] = $validated['description'];
                $partName = $validated['description'];
            }

            $repairPart = RepairPart::create($partData);

            // Créer une vente pour comptabiliser les pièces dans le CA Ventes
            $totalAmount = $validated['unit_price'] * $validated['quantity'];
            
            $sale = \App\Models\Sale::create([
                'shop_id' => $repair->shop_id,
                'user_id' => auth()->id(),
                'customer_id' => $repair->customer_id,
                'repair_id' => $repair->id,
                'is_repair_parts' => true,
                'client_type' => 'customer',
                'subtotal' => $totalAmount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => 0, // Sera payé à la livraison de la réparation
                'amount_due' => $totalAmount,
                'payment_status' => 'credit', // En attente de paiement
                'notes' => "Pièce pour réparation #{$repair->repair_number}: {$partName}",
            ]);

            // Créer l'item de vente si c'est un produit du stock
            if ($product) {
                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $validated['unit_price'],
                    'total_price' => $totalAmount,
                ]);

                // Sortie de stock via la vente
                StockMovement::create([
                    'shop_id' => $repair->shop_id,
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity' => -$validated['quantity'],
                    'unit_cost' => $product->purchase_price,
                    'reason' => "Vente pièce réparation #{$repair->repair_number}",
                    'moveable_type' => \App\Models\Sale::class,
                    'moveable_id' => $sale->id,
                    'user_id' => auth()->id(),
                ]);

                // Décrémenter le stock
                $product->decrement('stock_quantity', $validated['quantity']);
            }

            // Lier la vente à la pièce pour traçabilité
            $repairPart->update(['sale_id' => $sale->id]);

            // Mettre à jour le coût estimé si nécessaire
            $totalPartsCost = $repair->fresh()->parts_cost;
            if ($totalPartsCost > ($repair->estimated_cost ?? 0)) {
                $repair->update(['estimated_cost' => $totalPartsCost]);
            }

            ActivityLog::log('update', $repair, null, [
                'part_added' => $partName,
                'quantity' => $validated['quantity'],
                'sale_id' => $sale->id,
            ], "Ajout pièce réparation #{$repair->repair_number}");

            DB::commit();

            return back()->with('success', "Pièce ajoutée: {$validated['quantity']}x {$partName}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
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

        DB::beginTransaction();
        try {
            // Annuler la vente associée si elle existe
            if ($part->sale_id) {
                $sale = \App\Models\Sale::find($part->sale_id);
                if ($sale) {
                    // Supprimer les mouvements de stock liés à cette vente
                    $sale->stockMovements()->delete();
                    // Supprimer les items de vente
                    $sale->items()->delete();
                    // Supprimer la vente
                    $sale->delete();
                }
            }

            // Remettre en stock si c'est un produit
            if ($part->product) {
                $part->product->incrementStock($part->quantity);
            }

            $productName = $part->description ?? $part->product->name ?? 'Pièce';
            $part->delete();

            ActivityLog::log('update', $repair, null, ['part_removed' => $productName], "Retrait pièce réparation #{$repair->repair_number}");

            DB::commit();

            return back()->with('success', 'Pièce retirée et vente annulée.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
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
            abort(403, 'Cette réparation n\'est pas assignée à vous.');
        }
    }
}

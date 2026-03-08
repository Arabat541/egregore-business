<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\PendingSale;
use App\Models\PendingSaleItem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gestion des ventes en attente pour les revendeurs
 * Permet de cumuler les achats d'un revendeur sur la journée
 */
class PendingSaleController extends Controller
{
    /**
     * Liste des ventes en attente
     */
    public function index(Request $request)
    {
        $query = PendingSale::with(['reseller', 'user', 'items.product'])
            ->pending();

        if ($request->filled('reseller_id')) {
            $query->where('reseller_id', $request->reseller_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('sale_date', $request->date);
        } else {
            $query->forToday();
        }

        $pendingSales = $query->latest()->get();
        $resellers = Reseller::active()->orderBy('company_name')->get();

        return view('cashier.pending-sales.index', compact('pendingSales', 'resellers'));
    }

    /**
     * Formulaire pour ajouter des articles à la vente en attente d'un revendeur
     */
    public function create(Request $request)
    {
        $resellers = Reseller::active()->orderBy('company_name')->get();
        $products = Product::where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->orderBy('name')
            ->get();

        $selectedReseller = null;
        $pendingSale = null;

        if ($request->filled('reseller_id')) {
            $selectedReseller = Reseller::find($request->reseller_id);
            if ($selectedReseller) {
                $pendingSale = PendingSale::with('items.product')
                    ->forReseller($selectedReseller->id)
                    ->forToday()
                    ->pending()
                    ->first();
            }
        }

        return view('cashier.pending-sales.create', compact('resellers', 'products', 'selectedReseller', 'pendingSale'));
    }

    /**
     * Ajouter un article à la vente en attente
     */
    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'reseller_id' => 'required|exists:resellers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier le stock
        if (!$product->hasStock($validated['quantity'])) {
            return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
        }

        // Vérifier le prix minimum
        $minimumPrice = $product->wholesale_price ?? $product->semi_wholesale_price ?? $product->normal_price;
        if ($validated['unit_price'] < $minimumPrice) {
            return back()->with('error', "Le prix de vente de {$product->name} ({$validated['unit_price']} FCFA) est inférieur au prix minimum ({$minimumPrice} FCFA).");
        }

        DB::beginTransaction();
        try {
            // Obtenir ou créer la vente en attente du jour pour ce revendeur
            $pendingSale = PendingSale::getOrCreateForResellerToday(
                $validated['reseller_id'],
                auth()->id(),
                auth()->user()->shop_id
            );

            // Vérifier si ce produit existe déjà dans la vente en attente
            $existingItem = $pendingSale->items()->where('product_id', $validated['product_id'])->first();

            if ($existingItem) {
                // Mettre à jour la quantité
                $newQuantity = $existingItem->quantity + $validated['quantity'];
                
                // Revérifier le stock
                if (!$product->hasStock($newQuantity)) {
                    DB::rollBack();
                    return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
                }

                $existingItem->update([
                    'quantity' => $newQuantity,
                    'unit_price' => $validated['unit_price'],
                    'discount' => ($validated['discount'] ?? 0) + $existingItem->discount,
                    'total_price' => ($validated['unit_price'] * $newQuantity) - (($validated['discount'] ?? 0) + $existingItem->discount),
                ]);
            } else {
                // Créer nouvelle ligne
                PendingSaleItem::create([
                    'pending_sale_id' => $pendingSale->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'unit_price' => $validated['unit_price'],
                    'discount' => $validated['discount'] ?? 0,
                    'total_price' => ($validated['unit_price'] * $validated['quantity']) - ($validated['discount'] ?? 0),
                ]);
            }

            DB::commit();

            return redirect()->route('cashier.pending-sales.create', ['reseller_id' => $validated['reseller_id']])
                ->with('success', "{$product->name} ajouté à la vente en attente");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Modifier la quantité d'un article
     */
    public function updateItem(Request $request, PendingSaleItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $item->product;

        // Vérifier le stock
        if (!$product->hasStock($validated['quantity'])) {
            return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
        }

        $item->update([
            'quantity' => $validated['quantity'],
            'total_price' => ($item->unit_price * $validated['quantity']) - $item->discount,
        ]);

        return back()->with('success', 'Quantité mise à jour');
    }

    /**
     * Supprimer un article de la vente en attente
     */
    public function removeItem(PendingSaleItem $item)
    {
        $resellerId = $item->pendingSale->reseller_id;
        $productName = $item->product->name;
        
        $item->delete();

        return redirect()->route('cashier.pending-sales.create', ['reseller_id' => $resellerId])
            ->with('success', "{$productName} retiré de la vente en attente");
    }

    /**
     * Voir les détails d'une vente en attente
     */
    public function show(PendingSale $pendingSale)
    {
        $pendingSale->load(['reseller', 'user', 'items.product']);
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        return view('cashier.pending-sales.show', compact('pendingSale', 'paymentMethods'));
    }

    /**
     * Valider et convertir en vente réelle
     */
    public function validate(Request $request, PendingSale $pendingSale)
    {
        if (!$pendingSale->isPending()) {
            return back()->with('error', 'Cette vente a déjà été validée ou annulée.');
        }

        if ($pendingSale->items->isEmpty()) {
            return back()->with('error', 'Impossible de valider une vente sans articles.');
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount_given' => 'required|numeric|min:0',
            'is_credit' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // Vérifier la caisse
        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte. Veuillez ouvrir la caisse.');
        }

        $paymentMethod = \App\Models\PaymentMethod::find($validated['payment_method_id']);
        $reseller = $pendingSale->reseller;

        DB::beginTransaction();
        try {
            // Revérifier le stock de tous les produits
            foreach ($pendingSale->items as $item) {
                if (!$item->product->hasStock($item->quantity)) {
                    throw new \Exception("Stock insuffisant pour {$item->product->name}. Disponible: {$item->product->quantity_in_stock}");
                }
            }

            $totalAmount = $pendingSale->total_amount;
            $amountGiven = $validated['amount_given'];
            $amountPaid = min($amountGiven, $totalAmount);
            $amountDue = max(0, $totalAmount - $amountGiven);
            $isCredit = $request->boolean('is_credit') && $amountDue > 0;
            $paymentStatus = $isCredit ? 'credit' : 'paid';

            // Vérifier le crédit si nécessaire
            if ($isCredit && !$reseller->canPurchaseOnCredit($amountDue)) {
                throw new \Exception("Crédit insuffisant pour ce revendeur. Disponible: {$reseller->available_credit} FCFA");
            }

            // Créer la vente
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'reseller_id' => $reseller->id,
                'client_type' => 'reseller',
                'subtotal' => $totalAmount,
                'discount_amount' => $pendingSale->items->sum('discount'),
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'amount_given' => $amountGiven,
                'amount_due' => $amountDue,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod->type ?? 'cash',
                'notes' => $validated['notes'] ?? $pendingSale->notes,
                'completed_at' => now(),
            ]);

            // Créer les lignes de vente et sortie de stock
            foreach ($pendingSale->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'total_price' => $item->total_price,
                ]);

                // Sortie de stock
                StockMovement::recordExit(
                    $item->product,
                    auth()->user(),
                    $item->quantity,
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            // Mettre à jour la dette du revendeur si crédit
            if ($isCredit && $amountDue > 0) {
                $reseller->addDebt($amountDue);
            }

            // Enregistrer la transaction de caisse
            if ($amountPaid > 0) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_SALE,
                    $amountPaid,
                    $paymentMethod->code ?? 'cash',
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            // Marquer la vente en attente comme validée
            $pendingSale->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => auth()->id(),
                'sale_id' => $sale->id,
            ]);

            ActivityLog::log('sale', $sale, null, $sale->toArray(), "Vente #{$sale->invoice_number} (depuis vente en attente)");

            DB::commit();

            return redirect()->route('cashier.sales.receipt', ['sale' => $sale, 'auto' => 1])
                ->with('success', 'Vente validée avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Annuler une vente en attente
     */
    public function cancel(PendingSale $pendingSale)
    {
        if (!$pendingSale->isPending()) {
            return back()->with('error', 'Cette vente a déjà été validée ou annulée.');
        }

        $pendingSale->update(['status' => 'cancelled']);

        return redirect()->route('cashier.pending-sales.index')
            ->with('success', 'Vente en attente annulée');
    }
}

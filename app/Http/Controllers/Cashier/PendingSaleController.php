<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\PendingSale;
use App\Models\PendingSaleItem;
use App\Models\Product;
use App\Models\Reseller;
use App\Services\PendingSaleService;
use Illuminate\Http\Request;

/**
 * Gestion des ventes en attente pour les revendeurs
 * Permet de cumuler les achats d'un revendeur sur la journée
 */
class PendingSaleController extends Controller
{
    public function __construct(
        private readonly PendingSaleService $pendingSaleService,
    ) {}

    /**
     * Liste des ventes en attente
     */
    public function index(Request $request)
    {
        $query = PendingSale::with(['reseller', 'user', 'items.product.category'])
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
            ->with('category')
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
            'product_id'  => 'required|exists:products,id',
            'quantity'    => 'required|integer|min:1',
            'unit_price'  => 'required|numeric|min:0',
            'discount'    => 'nullable|numeric|min:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if (!$product->hasStock((int) $validated['quantity'])) {
            return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
        }

        $minimumPrice = (float) ($product->wholesale_price ?? $product->semi_wholesale_price ?? $product->normal_price);
        if ((float) $validated['unit_price'] < $minimumPrice) {
            return back()->with('error',
                "Le prix de vente de {$product->name} (" . number_format((float) $validated['unit_price'], 0, ',', ' ') . ' FCFA) '
                . 'est inférieur au prix minimum (' . number_format($minimumPrice, 0, ',', ' ') . ' FCFA).'
            );
        }

        try {
            $this->pendingSaleService->addItem($validated, auth()->user());

            return redirect()->route('cashier.pending-sales.create', ['reseller_id' => $validated['reseller_id']])
                ->with('success', "{$product->name} ajouté à la vente en attente");

        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur'));
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

        if (!$product->hasStock((int) $validated['quantity'])) {
            return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
        }

        $item->update([
            'quantity'    => $validated['quantity'],
            'total_price' => ($item->unit_price * $validated['quantity']) - $item->discount,
        ]);

        return back()->with('success', 'Quantité mise à jour');
    }

    /**
     * Supprimer un article de la vente en attente
     */
    public function removeItem(PendingSaleItem $item)
    {
        $resellerId  = $item->pendingSale->reseller_id;
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
        $pendingSale->load(['reseller', 'user', 'items.product.category']);
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

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
            'amount_given'      => 'required|numeric|min:0',
            'is_credit'         => 'nullable|boolean',
            'notes'             => 'nullable|string',
            'discount_amount'   => ['nullable', 'numeric', 'min:0', 'max:' . $pendingSale->total_amount],
        ]);

        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte. Veuillez ouvrir la caisse.');
        }

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);

        try {
            $sale = $this->pendingSaleService->validate(
                $pendingSale,
                $paymentMethod,
                (float) $validated['amount_given'],
                $validated['notes'] ?? null,
                $cashRegister,
                auth()->user(),
                (float) ($validated['discount_amount'] ?? 0),
            );

            return redirect()->route('cashier.sales.receipt', ['sale' => $sale, 'auto' => 1])
                ->with('success', 'Vente validée avec succès');

        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur'));
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

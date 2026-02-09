<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Gestion des ventes - Caissière uniquement
 */
class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'reseller', 'user']);

        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', "%{$request->search}%");
        }

        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(20);

        return view('cashier.sales.index', compact('sales'));
    }

    /**
     * Nouvelle vente - Interface POS
     */
    public function create()
    {
        // Vérifier si une caisse est ouverte
        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return redirect()->route('cashier.cash-register.open-form')
                ->with('warning', 'Veuillez ouvrir la caisse avant de faire une vente.');
        }

        $customers = Customer::active()->orderBy('first_name')->get();
        $resellers = Reseller::active()->orderBy('company_name')->get();
        $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $products = Product::where('is_active', true)->where('quantity_in_stock', '>', 0)->orderBy('name')->get();

        return view('cashier.sales.create', compact('customers', 'resellers', 'cashRegister', 'paymentMethods', 'products'));
    }

    /**
     * Enregistrer la vente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_type' => 'required|in:walk-in,customer,reseller',
            'customer_id' => 'required_if:client_type,customer|nullable|exists:customers,id',
            'reseller_id' => 'required_if:client_type,reseller|nullable|exists:resellers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'paid_amount' => 'required|numeric|min:0',
            'is_credit' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // Déterminer le statut de paiement
        $isCredit = $request->boolean('is_credit') && $validated['client_type'] === 'reseller';
        $paymentStatus = $isCredit ? 'credit' : 'paid';

        // Récupérer la méthode de paiement
        $paymentMethod = \App\Models\PaymentMethod::find($validated['payment_method_id']);

        // Vérifier la caisse
        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        // Vérifier le stock pour tous les produits
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (!$product->hasStock($item['quantity'])) {
                return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
            }
        }

        // Calculer le total
        $subtotal = collect($validated['items'])->sum(fn($i) => ($i['unit_price'] * $i['quantity']) - ($i['discount'] ?? 0));
        $total = $subtotal - ($validated['discount_amount'] ?? 0);
        $amountDue = $total - $validated['paid_amount'];

        // Vérifier le crédit pour les revendeurs
        if ($validated['client_type'] === 'reseller' && $paymentStatus === 'credit') {
            $reseller = Reseller::find($validated['reseller_id']);

            if (!$reseller->canPurchaseOnCredit($amountDue)) {
                return back()->with('error', "Crédit insuffisant pour ce revendeur. Disponible: {$reseller->available_credit} FCFA");
            }
        }

        // Clients particuliers et walk-in: paiement comptant obligatoire
        if (in_array($validated['client_type'], ['customer', 'walk-in']) && $validated['paid_amount'] < $total) {
            return back()->with('error', 'Les clients particuliers doivent payer comptant.');
        }

        DB::beginTransaction();
        try {
            // Calculer les totaux
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);
            }
            $discountAmount = $validated['discount_amount'] ?? 0;
            $totalAmount = $subtotal - $discountAmount;
            $amountPaid = $validated['paid_amount'];
            $amountDue = max(0, $totalAmount - $amountPaid);

            // Déterminer le statut réel basé sur le montant dû
            $finalPaymentStatus = ($amountDue > 0 && $validated['client_type'] === 'reseller') ? 'credit' : 'paid';

            // Montant effectivement encaissé (ne pas dépasser le total)
            $actualAmountPaid = min($amountPaid, $totalAmount);

            // Créer la vente
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'reseller_id' => $validated['reseller_id'] ?? null,
                'client_type' => $validated['client_type'] === 'walk-in' ? 'customer' : $validated['client_type'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => $actualAmountPaid,
                'amount_due' => $amountDue,
                'payment_status' => $finalPaymentStatus,
                'payment_method' => $paymentMethod->type ?? 'cash',
                'notes' => $validated['notes'] ?? null,
                'completed_at' => now(),
            ]);

            // Créer les lignes de vente et mettre à jour le stock
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'total_price' => ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0),
                ]);

                // Sortie de stock
                StockMovement::recordExit(
                    $product,
                    auth()->user(),
                    $item['quantity'],
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            // Mettre à jour la dette du revendeur si crédit
            if ($validated['client_type'] === 'reseller' && $amountDue > 0) {
                $reseller = Reseller::find($validated['reseller_id']);
                $reseller->addDebt($amountDue);
            }

            // Enregistrer la transaction de caisse
            // Enregistrer le montant réellement encaissé (total de la vente, pas l'argent donné)
            if ($actualAmountPaid > 0) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_SALE,
                    $actualAmountPaid,
                    $paymentMethod->code ?? 'cash',
                    $sale,
                    "Vente #{$sale->invoice_number}"
                );
            }

            ActivityLog::log('sale', $sale, null, $sale->toArray(), "Vente #{$sale->invoice_number}");

            DB::commit();

            // Rediriger directement vers le reçu pour impression automatique
            return redirect()->route('cashier.sales.receipt', ['sale' => $sale, 'auto' => 1]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'enregistrement de la vente: ' . $e->getMessage());
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'reseller', 'user', 'items.product']);
        return view('cashier.sales.show', compact('sale'));
    }

    /**
     * Recherche de produit par code-barres (AJAX)
     */
    public function findProduct(Request $request)
    {
        $search = $request->get('search');

        $product = Product::where(function ($q) use ($search) {
            $q->where('barcode', $search)
              ->orWhere('sku', $search);
        })
            ->active()
            ->first();

        if (!$product) {
            // Essayer une recherche par nom
            $products = Product::search($search)
                ->active()
                ->inStock()
                ->take(10)
                ->get();

            if ($products->isEmpty()) {
                return $this->errorResponse('Produit non trouvé', 404);
            }

            return $this->successResponse($products, 'Produits trouvés');
        }

        if (!$product->hasStock()) {
            return $this->errorResponse('Produit en rupture de stock', 400);
        }

        return $this->successResponse($product, 'Produit trouvé');
    }

    /**
     * Imprimer le ticket thermique avec QR code
     */
    public function printReceipt(Sale $sale)
    {
        $sale->load(['customer', 'reseller', 'items.product', 'user']);
        
        // Récupérer les paramètres de la boutique
        $settings = [
            'shop_name' => Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'shop_phone' => Setting::get('shop_phone', ''),
            'shop_address' => Setting::get('shop_address', ''),
            'shop_email' => Setting::get('shop_email', ''),
            'shop_siret' => Setting::get('shop_siret', ''),
        ];
        
        // Générer l'URL de suivi public
        $trackingUrl = route('track.sale', $sale->invoice_number);
        
        // Générer le QR code en base64
        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(150)
                ->margin(1)
                ->generate($trackingUrl)
        );
        
        return view('cashier.sales.thermal-ticket', compact('sale', 'settings', 'qrCode', 'trackingUrl'));
    }

    /**
     * Annuler une vente
     */
    public function cancel(Sale $sale)
    {
        // Vérifier que la vente peut être annulée (même jour uniquement)
        if (!$sale->created_at->isToday()) {
            return back()->with('error', 'Seules les ventes du jour peuvent être annulées.');
        }

        if ($sale->payment_status === 'cancelled') {
            return back()->with('error', 'Cette vente est déjà annulée.');
        }

        DB::beginTransaction();
        try {
            // Remettre le stock
            foreach ($sale->items as $item) {
                if ($item->product) {
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'user_id' => auth()->id(),
                        'type' => 'entry',
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->product->purchase_price,
                        'reason' => 'sale_cancelled',
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Annulation vente #{$sale->invoice_number}",
                    ]);

                    $item->product->increment('quantity_in_stock', $item->quantity);
                }
            }

            // Marquer la vente comme annulée
            $sale->update([
                'payment_status' => 'cancelled',
                'notes' => ($sale->notes ? $sale->notes . "\n" : '') . "Annulée le " . now()->format('d/m/Y H:i') . " par " . auth()->user()->name,
            ]);

            ActivityLog::log('cancel', $sale, null, ['status' => 'cancelled'], "Annulation vente #{$sale->invoice_number}");

            DB::commit();

            return back()->with('success', "Vente #{$sale->invoice_number} annulée. Le stock a été restauré.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }
}

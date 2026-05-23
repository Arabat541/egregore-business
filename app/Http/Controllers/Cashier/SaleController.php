<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Sale;
use App\Models\Setting;
use App\Events\SaleCompleted;
use App\Http\Requests\Cashier\StoreSaleRequest;
use App\Services\SaleService;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Gestion des ventes - Caissière uniquement
 */
class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

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

        $perPage = in_array((int) $request->input('per_page'), [10, 20, 50, 100]) ? (int) $request->input('per_page') : 20;
        $sales = $query->latest()->paginate($perPage)->withQueryString();

        return view('cashier.sales.index', compact('sales', 'perPage'));
    }

    /**
     * Export PDF des ventes (liste filtrée)
     */
    public function exportPdf(Request $request)
    {
        $query = Sale::with(['customer', 'reseller', 'user', 'items']);

        if ($request->filled('search'))         $query->where('invoice_number', 'like', "%{$request->search}%");
        if ($request->filled('client_type'))    $query->where('client_type', $request->client_type);
        if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
        if ($request->filled('date_from'))      $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('created_at', '<=', $request->date_to);

        $sales = $query->latest()->limit(500)->get();

        $totalRevenue = $sales->sum('total_amount');
        $totalPaid    = $sales->sum('amount_paid');
        $totalCredit  = $sales->where('payment_status', 'credit')->sum('total_amount');
        $dateFrom     = $request->date_from;
        $dateTo       = $request->date_to;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('cashier.sales.pdf', compact(
            'sales', 'totalRevenue', 'totalPaid', 'totalCredit', 'dateFrom', 'dateTo'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('ventes_' . date('Y-m-d') . '.pdf');
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
        $products = Product::with('category')->where('is_active', true)->where('quantity_in_stock', '>', 0)->orderBy('name')->get();

        return view('cashier.sales.create', compact('customers', 'resellers', 'cashRegister', 'paymentMethods', 'products'));
    }

    /**
     * Enregistrer la vente
     */
    public function store(StoreSaleRequest $request)
    {
        $validated = $request->validated();

        $cashRegister = CashRegister::getOpenRegisterForUser(auth()->id());
        if (!$cashRegister) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        $productIds = array_column($validated['items'], 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);
            if (!$product->hasStock($item['quantity'])) {
                return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
            }

            if ($validated['client_type'] === 'reseller' && !$product->reseller_price) {
                return back()->with('error', "⚠️ Le produit '{$product->name}' n'a pas de prix réparateur défini. Veuillez contacter l'administrateur pour définir ce prix avant de vendre à un réparateur.");
            }

            $correctPrice = $this->saleService->calculateCorrectPrice($product, (int) $item['quantity'], $validated['client_type']);
            if ((float) $item['unit_price'] !== (float) $correctPrice) {
                return back()->with('error', "⚠️ SÉCURITÉ: Le prix du produit {$product->name} ne peut pas être modifié! Prix attendu: {$correctPrice} FCFA.");
            }
        }

        $subtotal       = collect($validated['items'])->sum(fn($i) => ($i['unit_price'] * $i['quantity']) - ($i['discount'] ?? 0));
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);
        $total          = $subtotal - $discountAmount;
        $amountDue      = $total - $validated['paid_amount'];

        $reseller = null;
        if ($validated['client_type'] === 'reseller') {
            $reseller = Reseller::find($validated['reseller_id']);
        }

        if ($reseller && $amountDue > 0 && !$reseller->canPurchaseOnCredit($amountDue)) {
            return back()->with('error', "Crédit insuffisant pour ce revendeur. Disponible: {$reseller->available_credit} FCFA");
        }

        if (in_array($validated['client_type'], ['customer', 'walk-in']) && $validated['paid_amount'] < $total) {
            return back()->with('error', 'Les clients particuliers doivent payer comptant.');
        }

        try {
            $sale = $this->saleService->create($validated, auth()->user(), $cashRegister);

            SaleCompleted::dispatch($sale->load('items.product'));

            return redirect()->route('cashier.sales.receipt', ['sale' => $sale, 'auto' => 1]);

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'sale.store'));
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'reseller', 'user', 'items.product']);
        return view('cashier.sales.show', compact('sale'));
    }

    /**
     * Recherche de produit par nom ou SKU (AJAX)
     */
    public function findProduct(Request $request)
    {
        $search = $request->get('search');

        $product = Product::where('sku', $search)
            ->active()
            ->inStock()
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
        $sale->load(['customer', 'reseller', 'items.product', 'user', 'shop']);

        $shop = $sale->shop;
        $settings = [
            'shop_name'     => $shop?->name     ?: Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'shop_activity' => $shop?->activity ?: '',
            'shop_phone'    => $shop?->phone    ?: Setting::get('shop_phone', ''),
            'shop_address'  => $shop?->address  ?: Setting::get('shop_address', ''),
            'shop_email'    => $shop?->email    ?: Setting::get('shop_email', ''),
            'shop_siret'    => Setting::get('shop_siret', ''),
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
    public function cancel(Request $request, Sale $sale)
    {
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        try {
            $this->saleService->cancel($sale, $request->cancel_reason, auth()->user());

            return redirect()->route('cashier.sales.show', $sale)
                ->with('success', "Vente #{$sale->invoice_number} annulée. Stock et caisse restaurés.");

        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'sale.cancel'));
        }
    }

    /**
     * Formulaire de modification d'une vente
     */
    public function edit(Sale $sale)
    {
        if ($sale->payment_status === 'cancelled') {
            return redirect()->route('cashier.sales.show', $sale)
                ->with('error', 'Une vente annulée ne peut pas être modifiée.');
        }

        $sale->load(['customer', 'reseller', 'items.product.category', 'user']);
        $products = Product::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('cashier.sales.edit', compact('sale', 'products'));
    }

    /**
     * Enregistrer les modifications d'une vente
     */
    public function update(Request $request, Sale $sale)
    {
        if ($sale->payment_status === 'cancelled') {
            return redirect()->route('cashier.sales.show', $sale)
                ->with('error', 'Une vente annulée ne peut pas être modifiée.');
        }

        $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0',
            'discount_amount'     => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:1000',
        ]);

        $productIds = array_column($request->items, 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $clientType = $sale->client_type === 'reseller' ? 'reseller' : 'customer';

        foreach ($request->items as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) {
                return back()->with('error', 'Produit introuvable.');
            }
            $correctPrice = $this->saleService->calculateCorrectPrice($product, (int) $item['quantity'], $clientType);
            if ((float) $item['unit_price'] !== (float) $correctPrice) {
                return back()->with('error', "⚠️ SÉCURITÉ : le prix de {$product->name} ne peut pas être modifié. Prix attendu : {$correctPrice} FCFA.");
            }
        }

        try {
            $sale = $this->saleService->update(
                $sale,
                $request->items,
                (float) ($request->discount_amount ?? 0),
                $request->notes,
                auth()->user()
            );

            return redirect()->route('cashier.sales.show', $sale)
                ->with('success', "Vente #{$sale->invoice_number} modifiée avec succès.");

        } catch (\DomainException|\LogicException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'sale.update'));
        }
    }

    /**
     * Vérifier si le prix est en dessous du seuil minimum
     * Appelé en AJAX lors de la modification du prix de vente
     */
    public function checkMinimumPrice(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::find($request->product_id);
        $shopId = auth()->user()->shop_id;
        
        // Récupérer le prix minimum pour cette boutique
        $minimumPrice = $product->getMinimumPriceForShop($shopId);
        
        // Si pas de prix minimum défini pour cette boutique, pas d'alerte
        if ($minimumPrice === null) {
            return response()->json([
                'is_below_minimum' => false,
                'minimum_price' => null,
                'message' => null,
            ]);
        }

        $isBelowMinimum = $request->price < $minimumPrice;

        return response()->json([
            'is_below_minimum' => $isBelowMinimum,
            'minimum_price' => $minimumPrice,
            'price_entered' => $request->price,
            'difference' => $minimumPrice - $request->price,
            'message' => $isBelowMinimum 
                ? "⚠️ Prix en dessous du seuil ! Minimum: " . number_format($minimumPrice, 0, ',', ' ') . " FCFA"
                : null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierProductPrice;
use App\Models\SupplierPriceHistory;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Gestion des fournisseurs et commandes
 */
class SupplierController extends Controller
{
    /**
     * Liste des fournisseurs
     */
    public function index(Request $request)
    {
        $query = Supplier::withoutGlobalScope('shop');

        // Filtre par boutique
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->withCount('orders')
            ->orderBy('company_name')
            ->paginate(20)
            ->withQueryString();

        $shops = Shop::orderBy('name')->get();

        return view('admin.suppliers.index', compact('suppliers', 'shops'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $shops = Shop::orderBy('name')->get();
        
        return view('admin.suppliers.create', compact('categories', 'shops'));
    }

    /**
     * Enregistrer un fournisseur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'categories' => 'nullable|array',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        $validated['is_active'] = true;

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Fournisseur créé avec succès.');
    }

    /**
     * Afficher un fournisseur
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['orders' => fn($q) => $q->latest()->take(10), 'orders.items']);
        
        return view('admin.suppliers.show', compact('supplier'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Supplier $supplier)
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $shops = Shop::orderBy('name')->get();
        
        return view('admin.suppliers.edit', compact('supplier', 'categories', 'shops'));
    }

    /**
     * Mettre à jour un fournisseur
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'categories' => 'nullable|array',
            'shop_id' => 'nullable|exists:shops,id',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Fournisseur mis à jour avec succès.');
    }

    /**
     * Supprimer un fournisseur
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->orders()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce fournisseur car il a des commandes associées.');
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Fournisseur supprimé avec succès.');
    }

    /**
     * Liste des produits à commander (stock faible/rupture)
     */
    public function lowStockProducts(Request $request)
    {
        $shopId = $request->shop_id;
        
        $query = Product::withoutGlobalScope('shop')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('quantity_in_stock', 0)
                  ->orWhereColumn('quantity_in_stock', '<=', 'stock_alert_threshold');
            });

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->with('category')
            ->orderBy('quantity_in_stock')
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::active()->orderBy('company_name')->get();
        $shops = Shop::orderBy('name')->get();

        // Statistiques
        $outOfStock = $products->where('quantity_in_stock', 0)->count();
        $lowStock = $products->where('quantity_in_stock', '>', 0)->count();

        return view('admin.suppliers.low-stock', compact(
            'products', 'suppliers', 'shops', 'outOfStock', 'lowStock'
        ));
    }

    /**
     * Générer une commande PDF pour un fournisseur
     */
    public function generateOrderPdf(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);
        
        // Récupérer les produits avec les quantités
        $orderItems = [];
        foreach ($validated['products'] as $item) {
            $product = Product::find($item['id']);
            if ($product) {
                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'current_stock' => $product->quantity_in_stock,
                ];
            }
        }

        // Infos boutique
        $shop = auth()->user()->shop;
        $shopName = $shop?->name ?? Setting::get('shop_name', 'EGREGORE BUSINESS');
        $shopAddress = $shop?->address ?? Setting::get('shop_address', '');
        $shopPhone = $shop?->phone ?? Setting::get('shop_phone', '');
        $shopEmail = $shop?->email ?? Setting::get('shop_email', '');

        // Référence de commande
        $reference = SupplierOrder::generateReference();
        $orderDate = now();
        $notes = $validated['notes'] ?? '';

        $pdf = Pdf::loadView('admin.suppliers.order-pdf', compact(
            'supplier',
            'orderItems',
            'reference',
            'orderDate',
            'notes',
            'shopName',
            'shopAddress',
            'shopPhone',
            'shopEmail'
        ));

        $pdf->setPaper('A4', 'portrait');

        $filename = 'Commande_' . $reference . '_' . str_replace(' ', '_', $supplier->company_name) . '.pdf';

        // Option: sauvegarder la commande en base
        if ($request->has('save_order')) {
            DB::beginTransaction();
            try {
                $order = SupplierOrder::create([
                    'shop_id' => auth()->user()->shop_id,
                    'supplier_id' => $supplier->id,
                    'user_id' => auth()->id(),
                    'reference' => $reference,
                    'status' => 'draft',
                    'order_date' => $orderDate,
                    'notes' => $notes,
                ]);

                foreach ($orderItems as $item) {
                    SupplierOrderItem::create([
                        'supplier_order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'product_name' => $item['product']->name,
                        'quantity_ordered' => $item['quantity'],
                        'unit_price' => $item['product']->purchase_price,
                        'total_price' => $item['quantity'] * ($item['product']->purchase_price ?? 0),
                    ]);
                }

                $order->calculateTotal();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }

        return $pdf->download($filename);
    }

    /**
     * Prévisualiser la commande avant génération
     */
    public function previewOrder(Request $request)
    {
        $productIds = $request->input('products', []);
        
        $products = Product::whereIn('id', $productIds)
            ->with('category')
            ->get();

        $suppliers = Supplier::active()->orderBy('company_name')->get();

        return view('admin.suppliers.preview-order', compact('products', 'suppliers'));
    }

    /**
     * Liste des commandes
     */
    public function orders(Request $request)
    {
        $query = SupplierOrder::withoutGlobalScope('shop')
            ->with(['supplier', 'user', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $orders = $query->latest('order_date')->paginate(20)->withQueryString();
        $suppliers = Supplier::orderBy('company_name')->get();

        return view('admin.suppliers.orders', compact('orders', 'suppliers'));
    }

    /**
     * Afficher une commande
     */
    public function showOrder(SupplierOrder $order)
    {
        $order->load(['supplier', 'user', 'items.product']);
        
        return view('admin.suppliers.order-show', compact('order'));
    }

    /**
     * Marquer une commande comme envoyée
     */
    public function markOrderSent(SupplierOrder $order)
    {
        $order->markAsSent();
        
        return back()->with('success', 'Commande marquée comme envoyée.');
    }

    /**
     * Marquer une commande comme reçue
     */
    public function markOrderReceived(Request $request, SupplierOrder $order)
    {
        DB::beginTransaction();
        try {
            // Mettre à jour les quantités reçues et les prix
            foreach ($order->items as $item) {
                $receivedQty = $request->input("received.{$item->id}", $item->quantity_ordered);
                $unitPrice = $request->input("prices.{$item->id}", $item->unit_price);
                
                $item->update([
                    'quantity_received' => $receivedQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $receivedQty * $unitPrice,
                ]);

                // Mettre à jour le stock du produit
                if ($item->product) {
                    $item->product->increment('quantity_in_stock', $receivedQty);
                    
                    // Enregistrer le prix fournisseur
                    $this->recordSupplierPrice(
                        $order->supplier_id,
                        $item->product_id,
                        $unitPrice,
                        $order->id
                    );
                }
            }

            $order->calculateTotal();
            $order->markAsReceived();
            DB::commit();

            return back()->with('success', 'Commande marquée comme reçue, stock et prix fournisseurs mis à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Enregistrer/Mettre à jour le prix d'un fournisseur pour un produit
     */
    protected function recordSupplierPrice(int $supplierId, int $productId, float $price, ?int $orderId = null): void
    {
        $existingPrice = SupplierProductPrice::where('supplier_id', $supplierId)
            ->where('product_id', $productId)
            ->first();

        if ($existingPrice) {
            // Mettre à jour avec historique
            $existingPrice->updatePrice($price, $orderId, auth()->id());
        } else {
            // Créer nouveau prix
            SupplierProductPrice::create([
                'supplier_id' => $supplierId,
                'product_id' => $productId,
                'unit_price' => $price,
                'price_updated_at' => now(),
            ]);

            // Enregistrer dans l'historique
            SupplierPriceHistory::create([
                'supplier_id' => $supplierId,
                'product_id' => $productId,
                'unit_price' => $price,
                'supplier_order_id' => $orderId,
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ]);
        }
    }

    /**
     * Afficher les prix des fournisseurs pour un produit
     */
    public function productPrices(Product $product)
    {
        $prices = $product->supplierPrices()
            ->with('supplier:id,company_name,phone,whatsapp')
            ->orderBy('unit_price', 'asc')
            ->get();

        $priceHistory = $product->priceHistory()
            ->with(['supplier:id,company_name', 'order:id,reference'])
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.suppliers.product-prices', compact('product', 'prices', 'priceHistory'));
    }

    /**
     * Gérer les prix d'un fournisseur
     */
    public function supplierPrices(Supplier $supplier)
    {
        $prices = $supplier->productPrices()
            ->with('product:id,name,sku,purchase_price')
            ->orderBy('updated_at', 'desc')
            ->paginate(50);

        return view('admin.suppliers.supplier-prices', compact('supplier', 'prices'));
    }

    /**
     * Ajouter/Modifier un prix produit pour un fournisseur
     */
    public function storePrice(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'unit_price' => 'required|numeric|min:0',
            'min_order_quantity' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->recordSupplierPrice(
            $validated['supplier_id'],
            $validated['product_id'],
            $validated['unit_price']
        );

        // Mettre à jour les infos supplémentaires
        $price = SupplierProductPrice::where('supplier_id', $validated['supplier_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($price) {
            $price->update([
                'min_order_quantity' => $validated['min_order_quantity'] ?? 1,
                'lead_time_days' => $validated['lead_time_days'],
                'notes' => $validated['notes'],
            ]);
        }

        return back()->with('success', 'Prix fournisseur enregistré.');
    }

    /**
     * Comparaison des prix pour les produits à commander
     */
    public function priceComparison(Request $request)
    {
        $shopId = $request->shop_id;
        
        // Produits avec stock faible + leurs prix fournisseurs
        $query = Product::withoutGlobalScope('shop')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('quantity_in_stock', 0)
                  ->orWhereColumn('quantity_in_stock', '<=', 'stock_alert_threshold');
            });

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->with(['category', 'supplierPrices.supplier'])
            ->orderBy('quantity_in_stock')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                // Ajouter le fournisseur le moins cher
                $cheapest = $product->supplierPrices->sortBy('unit_price')->first();
                $product->cheapest_supplier = $cheapest;
                $product->all_prices = $product->supplierPrices->sortBy('unit_price');
                return $product;
            });

        $suppliers = Supplier::active()->orderBy('company_name')->get();
        $shops = Shop::orderBy('name')->get();

        return view('admin.suppliers.price-comparison', compact('products', 'suppliers', 'shops'));
    }
}

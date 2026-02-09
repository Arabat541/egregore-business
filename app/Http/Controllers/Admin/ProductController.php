<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\SupplierProductPrice;
use App\Models\SupplierPriceHistory;
use Illuminate\Http\Request;

/**
 * Gestion des produits et du stock - Admin uniquement
 * L'admin gère le stock par boutique
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Admin voit tous les produits, peut filtrer par boutique
        $query = Product::withoutGlobalScope('shop')->with(['category', 'shop']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filtre par boutique pour l'admin
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('stock_status')) {
            match ($request->stock_status) {
                'low' => $query->lowStock(),
                'low_stock' => $query->lowStock(),
                'out' => $query->outOfStock(),
                'out_of_stock' => $query->outOfStock(),
                'in_stock' => $query->inStock(),
                default => null,
            };
        }

        // Filtre par fournisseur
        if ($request->filled('supplier_id')) {
            if ($request->supplier_id === 'none') {
                // Produits sans fournisseur associé
                $query->whereDoesntHave('supplierPrices');
            } else {
                // Produits de ce fournisseur
                $query->whereHas('supplierPrices', function ($q) use ($request) {
                    $q->where('supplier_id', $request->supplier_id);
                });
            }
        }

        $products = $query->latest()->paginate(20);
        $categories = Category::active()->ordered()->get();
        $shops = Shop::active()->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories', 'shops'));
    }

    public function create()
    {
        $categories = Category::active()->ordered()->get();
        $shops = Shop::active()->orderBy('name')->get();
        return view('admin.products.form', compact('categories', 'shops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode',
            'sku' => 'nullable|string|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'shop_id' => 'required|exists:shops,id',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'stock_alert_threshold' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            // Fournisseur optionnel
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_price' => 'nullable|numeric|min:0',
            'supplier_lead_time' => 'nullable|integer|min:0',
        ]);

        // Créer le produit (sans les champs fournisseur)
        $productData = collect($validated)->except(['supplier_id', 'supplier_price', 'supplier_lead_time'])->toArray();
        $product = Product::create($productData);

        // Enregistrer le mouvement de stock initial
        if ($product->quantity_in_stock > 0) {
            StockMovement::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => StockMovement::TYPE_ENTRY,
                'quantity' => $product->quantity_in_stock,
                'quantity_before' => 0,
                'quantity_after' => $product->quantity_in_stock,
                'reason' => 'Stock initial',
            ]);
        }

        // Associer le fournisseur si spécifié
        if ($request->filled('supplier_id')) {
            $supplierPrice = $request->supplier_price ?: $product->purchase_price;
            
            // Créer l'association produit-fournisseur avec le prix
            SupplierProductPrice::create([
                'supplier_id' => $request->supplier_id,
                'product_id' => $product->id,
                'unit_price' => $supplierPrice,
                'lead_time_days' => $request->supplier_lead_time,
                'price_updated_at' => now(),
            ]);

            // Enregistrer dans l'historique des prix
            SupplierPriceHistory::create([
                'supplier_id' => $request->supplier_id,
                'product_id' => $product->id,
                'unit_price' => $supplierPrice,
                'recorded_by' => auth()->id(),
                'notes' => 'Prix initial à la création du produit',
                'recorded_at' => now(),
            ]);
        }

        ActivityLog::log('create', $product, null, $product->toArray(), "Création produit: {$product->name}");

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'shop', 'stockMovements' => function ($q) {
            $q->with('user')->latest()->take(20);
        }]);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::active()->ordered()->get();
        $shops = Shop::active()->orderBy('name')->get();
        return view('admin.products.form', compact('product', 'categories', 'shops'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'shop_id' => 'required|exists:shops,id',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'stock_alert_threshold' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $oldValues = $product->toArray();
        $product->update($validated);

        ActivityLog::log('update', $product, $oldValues, $product->toArray(), "Modification produit: {$product->name}");

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product)
    {
        // Vérifier si le produit a des ventes ou réparations
        if ($product->saleItems()->exists() || $product->repairParts()->exists()) {
            return back()->with('error', 'Impossible de supprimer ce produit car il est lié à des ventes ou réparations.');
        }

        ActivityLog::log('delete', $product, $product->toArray(), null, "Suppression produit: {$product->name}");

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

    /**
     * Formulaire de réapprovisionnement
     */
    public function stockEntry(Product $product)
    {
        return view('admin.products.stock-entry', compact('product'));
    }

    /**
     * Enregistrer une entrée de stock
     */
    public function storeStockEntry(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $reason = $validated['notes'] ?? 'Réapprovisionnement';
        if ($validated['reference'] ?? null) {
            $reason = "Réf: {$validated['reference']} - " . $reason;
        }

        // Mettre à jour le prix d'achat si fourni
        $unitPrice = $validated['unit_price'] ?? $product->purchase_price;
        if (isset($validated['unit_price']) && $validated['unit_price'] != $product->purchase_price) {
            $product->update(['purchase_price' => $validated['unit_price']]);
        }

        StockMovement::recordEntry(
            $product,
            auth()->user(),
            $validated['quantity'],
            $reason
        );

        // Enregistrer le prix fournisseur si un fournisseur est sélectionné
        if ($request->filled('supplier_id')) {
            $existingPrice = SupplierProductPrice::where('supplier_id', $validated['supplier_id'])
                ->where('product_id', $product->id)
                ->first();

            if ($existingPrice) {
                // Mettre à jour le prix existant
                if ($existingPrice->unit_price != $unitPrice) {
                    $existingPrice->updatePrice($unitPrice, null, auth()->id(), $reason);
                }
            } else {
                // Créer nouvelle association
                SupplierProductPrice::create([
                    'supplier_id' => $validated['supplier_id'],
                    'product_id' => $product->id,
                    'unit_price' => $unitPrice,
                    'price_updated_at' => now(),
                ]);

                // Enregistrer dans l'historique
                SupplierPriceHistory::create([
                    'supplier_id' => $validated['supplier_id'],
                    'product_id' => $product->id,
                    'unit_price' => $unitPrice,
                    'recorded_by' => auth()->id(),
                    'notes' => $reason,
                    'recorded_at' => now(),
                ]);
            }
        }

        ActivityLog::log('stock_entry', $product, null, [
            'quantity' => $validated['quantity'],
            'new_stock' => $product->fresh()->quantity_in_stock,
            'supplier_id' => $validated['supplier_id'] ?? null,
        ], "Entrée stock: {$validated['quantity']} x {$product->name}");

        return redirect()->route('admin.products.show', $product)
            ->with('success', "Stock mis à jour: +{$validated['quantity']} unités.");
    }

    /**
     * Liste des produits en stock faible
     */
    public function lowStock()
    {
        $products = Product::lowStock()
            ->with('category')
            ->orderBy('quantity_in_stock')
            ->paginate(20);

        return view('admin.products.low-stock', compact('products'));
    }

    /**
     * Recherche par code-barres (AJAX)
     */
    public function findByBarcode(Request $request)
    {
        $product = Product::where('barcode', $request->barcode)
            ->active()
            ->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'product' => $product->load('category'),
        ]);
    }
}

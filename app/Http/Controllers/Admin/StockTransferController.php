<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockTransferController extends Controller
{
    /**
     * Liste des transferts de stock
     */
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromShop', 'toShop', 'user', 'validatedBy'])
            ->orderBy('created_at', 'desc');
        
        // Filtres
        if ($request->filled('from_shop_id')) {
            $query->where('from_shop_id', $request->from_shop_id);
        }
        
        if ($request->filled('to_shop_id')) {
            $query->where('to_shop_id', $request->to_shop_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $transfers = $query->paginate(20)->withQueryString();
        $shops = Shop::orderBy('name')->get();
        
        return view('admin.stock-transfers.index', compact('transfers', 'shops'));
    }

    /**
     * Formulaire de création d'un transfert
     */
    public function create()
    {
        $shops = Shop::orderBy('name')->get();
        
        return view('admin.stock-transfers.create', compact('shops'));
    }

    /**
     * Récupérer les produits d'une boutique (API)
     */
    public function getShopProducts(Shop $shop)
    {
        $products = Product::where('shop_id', $shop->id)
            ->where('quantity_in_stock', '>', 0)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name ?? '-',
                    'quantity' => $product->quantity_in_stock,
                    'purchase_price' => $product->purchase_price,
                    'normal_price' => $product->normal_price,
                ];
            });
        
        return response()->json($products);
    }

    /**
     * Enregistrer un nouveau transfert
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_shop_id' => 'required|exists:shops,id',
            'to_shop_id' => 'required|exists:shops,id|different:from_shop_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Vérifier que les produits appartiennent à la boutique source
        // et que les quantités sont disponibles
        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('shop_id', $request->from_shop_id)
                ->first();
            
            if (!$product) {
                return back()->with('error', 'Un produit sélectionné n\'appartient pas à la boutique source.');
            }
            
            if ($product->quantity_in_stock < $item['quantity']) {
                return back()->with('error', "Stock insuffisant pour le produit {$product->name}. Disponible: {$product->quantity_in_stock}");
            }
        }
        
        DB::beginTransaction();
        
        try {
            // Créer le transfert
            $transfer = StockTransfer::create([
                'reference' => 'TRF-' . strtoupper(uniqid()),
                'from_shop_id' => $request->from_shop_id,
                'to_shop_id' => $request->to_shop_id,
                'user_id' => Auth::id(),
                'status' => StockTransfer::STATUS_PENDING,
                'notes' => $request->notes,
            ]);
            
            // Créer les items du transfert
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $product->purchase_price,
                    'notes' => $item['notes'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('admin.stock-transfers.show', $transfer)
                ->with('success', 'Transfert créé avec succès. En attente de validation.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la création du transfert: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'un transfert
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'fromShop', 
            'toShop', 
            'user', 
            'validatedBy',
            'items.product.category'
        ]);
        
        return view('admin.stock-transfers.show', compact('stockTransfer'));
    }

    /**
     * Valider et exécuter le transfert
     */
    public function validate(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== StockTransfer::STATUS_PENDING) {
            return back()->with('error', 'Ce transfert ne peut plus être validé.');
        }
        
        // Vérifier que les stocks sont toujours disponibles
        foreach ($stockTransfer->items as $item) {
            $product = Product::where('id', $item->product_id)
                ->where('shop_id', $stockTransfer->from_shop_id)
                ->first();
            
            if (!$product || $product->quantity_in_stock < $item->quantity) {
                return back()->with('error', "Stock insuffisant pour le produit {$item->product->name}.");
            }
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($stockTransfer->items as $item) {
                // Produit source
                $sourceProduct = Product::where('id', $item->product_id)
                    ->where('shop_id', $stockTransfer->from_shop_id)
                    ->first();
                
                $sourceStockBefore = $sourceProduct->quantity_in_stock;
                $sourceStockAfter = $sourceStockBefore - $item->quantity;
                
                // Diminuer le stock source
                $sourceProduct->update(['quantity_in_stock' => $sourceStockAfter]);
                
                // Mouvement sortant
                StockMovement::create([
                    'shop_id' => $stockTransfer->from_shop_id,
                    'product_id' => $item->product_id,
                    'user_id' => Auth::id(),
                    'type' => 'transfer_out',
                    'quantity' => -$item->quantity,
                    'quantity_before' => $sourceStockBefore,
                    'quantity_after' => $sourceStockAfter,
                    'reference' => $stockTransfer->reference,
                    'reason' => "Transfert vers {$stockTransfer->toShop->name}",
                ]);
                
                // Chercher le produit dans la boutique destination par nom et catégorie
                $destProduct = Product::where('shop_id', $stockTransfer->to_shop_id)
                    ->where('name', $sourceProduct->name)
                    ->where('category_id', $sourceProduct->category_id)
                    ->first();
                
                if ($destProduct) {
                    // Le produit existe, augmenter le stock
                    $destStockBefore = $destProduct->quantity_in_stock;
                    $destStockAfter = $destStockBefore + $item->quantity;
                    $destProduct->update(['quantity_in_stock' => $destStockAfter]);
                } else {
                    // Créer le produit dans la boutique destination
                    $destProduct = Product::create([
                        'shop_id' => $stockTransfer->to_shop_id,
                        'category_id' => $sourceProduct->category_id,
                        'name' => $sourceProduct->name,
                        'sku' => $sourceProduct->sku,
                        'description' => $sourceProduct->description,
                        'purchase_price' => $sourceProduct->purchase_price,
                        'normal_price' => $sourceProduct->normal_price,
                        'semi_wholesale_price' => $sourceProduct->semi_wholesale_price,
                        'wholesale_price' => $sourceProduct->wholesale_price,
                        'quantity_in_stock' => $item->quantity,
                        'stock_alert_threshold' => $sourceProduct->stock_alert_threshold,
                        'is_active' => true,
                    ]);
                    $destStockBefore = 0;
                    $destStockAfter = $item->quantity;
                }
                
                // Mouvement entrant
                StockMovement::create([
                    'shop_id' => $stockTransfer->to_shop_id,
                    'product_id' => $destProduct->id,
                    'user_id' => Auth::id(),
                    'type' => 'transfer_in',
                    'quantity' => $item->quantity,
                    'quantity_before' => $destStockBefore,
                    'quantity_after' => $destStockAfter,
                    'reference' => $stockTransfer->reference,
                    'reason' => "Transfert depuis {$stockTransfer->fromShop->name}",
                ]);
            }
            
            // Mettre à jour le transfert
            $stockTransfer->update([
                'status' => StockTransfer::STATUS_COMPLETED,
                'validated_by' => Auth::id(),
                'validated_at' => now(),
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.stock-transfers.show', $stockTransfer)
                ->with('success', 'Transfert validé et exécuté avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la validation du transfert: ' . $e->getMessage());
        }
    }

    /**
     * Annuler un transfert en attente
     */
    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== StockTransfer::STATUS_PENDING) {
            return back()->with('error', 'Ce transfert ne peut plus être annulé.');
        }
        
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);
        
        $stockTransfer->update([
            'status' => StockTransfer::STATUS_CANCELLED,
            'notes' => $stockTransfer->notes . "\n\n[ANNULÉ] " . $request->cancel_reason,
        ]);
        
        return redirect()->route('admin.stock-transfers.index')
            ->with('success', 'Transfert annulé avec succès.');
    }
}

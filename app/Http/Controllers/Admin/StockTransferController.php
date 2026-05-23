<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Shop;
use App\Models\Product;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $transferService,
    ) {}
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
            'from_shop_id'           => 'required|exists:shops,id',
            'to_shop_id'             => 'required|exists:shops,id|different:from_shop_id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('shop_id', $request->from_shop_id)
                ->first();

            if (!$product) {
                return back()->with('error', "Un produit sélectionné n'appartient pas à la boutique source.");
            }

            if ($product->quantity_in_stock < $item['quantity']) {
                return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
            }
        }

        try {
            $transfer = $this->transferService->create(
                (int) $request->from_shop_id,
                (int) $request->to_shop_id,
                Auth::id(),
                $request->items,
                $request->notes,
            );

            return redirect()->route('admin.stock-transfers.show', $transfer)
                ->with('success', 'Transfert créé avec succès. En attente de validation.');

        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors de la création du transfert'));
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
     * Expédier le transfert : déduit le stock source, passe en "en transit".
     */
    public function validate(Request $request, StockTransfer $stockTransfer)
    {
        try {
            $this->transferService->ship($stockTransfer, Auth::id());

            return redirect()->route('admin.stock-transfers.show', $stockTransfer)
                ->with('success', 'Transfert expédié. La boutique destination doit maintenant confirmer la réception.');

        } catch (\LogicException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, "Erreur lors de l'expédition"));
        }
    }

    /**
     * Confirmer la réception par la boutique destination.
     */
    public function confirmReception(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'items'                     => 'required|array',
            'items.*.item_id'           => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'reception_notes'           => 'nullable|string|max:1000',
        ]);

        try {
            $hasDiscrepancy = $this->transferService->confirmReception(
                $stockTransfer,
                $request->items,
                $request->reception_notes,
                Auth::id(),
            );

            $msg = $hasDiscrepancy
                ? 'Réception confirmée avec écarts. Les stocks ont été ajustés et les écarts sont tracés.'
                : 'Réception confirmée avec succès. Tous les articles correspondent.';

            return redirect()->route('admin.stock-transfers.show', $stockTransfer)->with('success', $msg);

        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $this->handleException($e, 'Erreur lors de la confirmation de réception'));
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

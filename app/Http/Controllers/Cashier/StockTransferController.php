<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Services\NotificationService;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gestion des transferts de stock côté caissière.
 * - Consultation + confirmation des transferts entrants
 * - Initiation de transferts sortants (avec notification admin)
 */
class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $transferService,
    ) {}

    /**
     * Liste des transferts entrants et sortants pour la boutique de la caissière
     */
    public function index(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        // Transferts entrants
        $inQuery = StockTransfer::with(['fromShop', 'toShop', 'user'])
            ->where('to_shop_id', $shopId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $inQuery->where('status', $request->status);
        } else {
            $inQuery->whereIn('status', ['in_transit', 'received', 'completed']);
        }

        if ($request->filled('date_from')) {
            $inQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $inQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $transfers    = $inQuery->paginate(20)->withQueryString();
        $pendingCount = StockTransfer::where('to_shop_id', $shopId)
                            ->where('status', 'in_transit')
                            ->count();

        // Transferts sortants (initiés par cette boutique)
        $outgoingTransfers = StockTransfer::with(['fromShop', 'toShop', 'user'])
            ->where('from_shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('cashier.stock-transfers.index', compact('transfers', 'pendingCount', 'outgoingTransfers'));
    }

    /**
     * Formulaire d'initiation d'un transfert sortant
     */
    public function create()
    {
        $user  = Auth::user();
        $shops = Shop::where('id', '!=', $user->shop_id)->orderBy('name')->get();

        return view('cashier.stock-transfers.create', compact('shops'));
    }

    /**
     * Retourne les produits disponibles de la boutique de la caissière (API JSON)
     */
    public function getMyProducts()
    {
        $products = Product::where('shop_id', Auth::user()->shop_id)
            ->where('quantity_in_stock', '>', 0)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'sku'            => $p->sku,
                'category'       => $p->category->name ?? '-',
                'quantity'       => $p->quantity_in_stock,
                'purchase_price' => $p->purchase_price,
            ]);

        return response()->json($products);
    }

    /**
     * Enregistrer un transfert sortant initié par la caissière
     */
    public function store(Request $request, NotificationService $notifications)
    {
        $request->validate([
            'to_shop_id'             => 'required|exists:shops,id|different:from_shop_id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $user   = Auth::user();
        $shopId = $user->shop_id;

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('shop_id', $shopId)
                ->first();

            if (!$product) {
                return back()->with('error', "Un produit sélectionné n'appartient pas à votre boutique.");
            }

            if ($product->quantity_in_stock < $item['quantity']) {
                return back()->with('error', "Stock insuffisant pour {$product->name}. Disponible: {$product->quantity_in_stock}");
            }
        }

        try {
            $transfer = $this->transferService->create(
                $shopId,
                (int) $request->to_shop_id,
                $user->id,
                $request->items,
                $request->notes,
            );

            $toShop = Shop::find($request->to_shop_id);
            $notifications->notifyRole(
                'admin',
                Notification::TYPE_STOCK_TRANSFER_REQUESTED,
                'Demande de transfert de stock',
                "{$user->name} ({$user->shop->name}) demande un transfert vers {$toShop->name} ({$transfer->total_items} article(s)).",
                route('admin.stock-transfers.show', $transfer),
                $transfer
            );

            return redirect()->route('cashier.stock-transfers.index')
                ->with('success', "Demande de transfert {$transfer->reference} envoyée. L'administrateur va valider et expédier.");

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création du transfert: ' . $e->getMessage());
        }
    }

    /**
     * Détail d'un transfert entrant
     */
    public function show(StockTransfer $stockTransfer)
    {
        // Vérifier que ce transfert est bien destiné à la boutique de la caissière
        if ($stockTransfer->to_shop_id !== Auth::user()->shop_id) {
            abort(403, 'Ce transfert ne concerne pas votre boutique.');
        }

        $stockTransfer->load([
            'fromShop', 'toShop', 'user', 'sentBy', 'receivedBy',
            'items' => function ($q) {
                $q->with(['product' => function ($pq) {
                    $pq->withoutGlobalScope('shop')->with('category');
                }]);
            },
        ]);

        return view('cashier.stock-transfers.show', compact('stockTransfer'));
    }

    /**
     * Confirmer la réception du transfert côté caissière destination
     */
    public function confirmReception(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->to_shop_id !== Auth::user()->shop_id) {
            abort(403, 'Ce transfert ne concerne pas votre boutique.');
        }

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

            return redirect()->route('cashier.stock-transfers.show', $stockTransfer)->with('success', $msg);

        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la confirmation : ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    /**
     * Liste des inventaires
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['shop', 'user', 'validatedBy']);

        // Filtres
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
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

        $inventories = $query->latest()->paginate(20);
        $shops = Shop::where('is_active', true)->get();

        return view('admin.inventory.index', compact('inventories', 'shops'));
    }

    /**
     * Formulaire de création d'inventaire
     */
    public function create()
    {
        $shops = Shop::where('is_active', true)->get();
        
        // Vérifier s'il y a un inventaire en cours
        $inProgressInventory = Inventory::inProgress()->first();
        if ($inProgressInventory) {
            return redirect()->route('admin.inventory.show', $inProgressInventory)
                ->with('warning', 'Un inventaire est déjà en cours. Veuillez le terminer ou l\'annuler avant d\'en créer un nouveau.');
        }

        return view('admin.inventory.create', compact('shops'));
    }

    /**
     * Créer un nouvel inventaire
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'notes' => 'nullable|string',
        ]);

        // Vérifier qu'il n'y a pas d'inventaire en cours pour cette boutique
        $existing = Inventory::where('shop_id', $validated['shop_id'])
            ->inProgress()
            ->first();

        if ($existing) {
            return back()->with('error', 'Un inventaire est déjà en cours pour cette boutique.');
        }

        // Créer l'inventaire
        $inventory = Inventory::create([
            'shop_id' => $validated['shop_id'],
            'user_id' => Auth::id(),
            'reference' => Inventory::generateReference(),
            'status' => Inventory::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // Créer les items pour tous les produits de la boutique
        $products = Product::where('shop_id', $validated['shop_id'])
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            InventoryItem::create([
                'inventory_id' => $inventory->id,
                'product_id' => $product->id,
                'theoretical_quantity' => $product->quantity_in_stock,
            ]);
        }

        $inventory->update(['total_products' => $products->count()]);

        return redirect()->route('admin.inventory.show', $inventory)
            ->with('success', 'Inventaire créé. Vous pouvez maintenant scanner les produits.');
    }

    /**
     * Afficher l'inventaire (interface de comptage)
     */
    public function show(Inventory $inventory)
    {
        $inventory->load(['shop', 'user', 'validatedBy', 'items.product.category']);
        
        $stats = [
            'total' => $inventory->items()->count(),
            'counted' => $inventory->items()->where('counted', true)->count(),
            'with_difference' => $inventory->items()->where('difference', '!=', 0)->count(),
            'shortage' => $inventory->items()->where('difference', '<', 0)->count(),
            'surplus' => $inventory->items()->where('difference', '>', 0)->count(),
        ];

        return view('admin.inventory.show', compact('inventory', 'stats'));
    }

    /**
     * Mettre à jour la quantité comptée d'un article
     */
    public function updateItem(Request $request, Inventory $inventory, InventoryItem $item)
    {
        if ($inventory->status !== Inventory::STATUS_IN_PROGRESS) {
            return response()->json(['error' => 'Inventaire non modifiable'], 422);
        }

        $validated = $request->validate([
            'physical_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $item->updateCount($validated['physical_quantity'], $validated['notes'] ?? null);

        // Calculer les nouvelles statistiques
        $stats = [
            'total' => $inventory->items()->count(),
            'counted' => $inventory->items()->where('counted', true)->count(),
            'with_difference' => $inventory->items()->where('counted', true)->where('difference', '!=', 0)->count(),
            'shortage' => $inventory->items()->where('counted', true)->where('difference', '<', 0)->count(),
            'surplus' => $inventory->items()->where('counted', true)->where('difference', '>', 0)->count(),
        ];
        
        $progress = $stats['total'] > 0 ? round(($stats['counted'] / $stats['total']) * 100) : 0;

        return response()->json([
            'success' => true,
            'item' => $item->fresh()->load('product'),
            'progress' => $progress,
            'stats' => $stats,
        ]);
    }

    /**
     * Rechercher un produit par nom
     */
    public function searchProduct(Request $request, Inventory $inventory)
    {
        if ($inventory->status !== Inventory::STATUS_IN_PROGRESS) {
            return response()->json(['error' => 'Inventaire non modifiable'], 422);
        }

        $validated = $request->validate([
            'search' => 'required|string|min:2',
        ]);

        // Rechercher les produits par nom
        $items = $inventory->items()
            ->with('product.category')
            ->whereHas('product', function($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%');
            })
            ->limit(10)
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Aucun produit trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    /**
     * Obtenir un article d'inventaire spécifique
     */
    public function getItem(Inventory $inventory, InventoryItem $item)
    {
        return response()->json([
            'success' => true,
            'item' => $item->load('product'),
        ]);
    }

    /**
     * Terminer l'inventaire (calculer les écarts)
     */
    public function complete(Inventory $inventory)
    {
        if ($inventory->status !== Inventory::STATUS_IN_PROGRESS) {
            return back()->with('error', 'Cet inventaire ne peut pas être terminé.');
        }

        $inventory->complete();

        return redirect()->route('admin.inventory.show', $inventory)
            ->with('success', 'Inventaire terminé. Vous pouvez maintenant le valider pour corriger le stock.');
    }

    /**
     * Valider l'inventaire et corriger le stock
     */
    public function validateInventory(Inventory $inventory)
    {
        if ($inventory->status !== Inventory::STATUS_COMPLETED) {
            return back()->with('error', 'L\'inventaire doit être terminé avant validation.');
        }

        try {
            $inventory->validate(Auth::id());
            return redirect()->route('admin.inventory.show', $inventory)
                ->with('success', 'Inventaire validé. Le stock a été corrigé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Annuler l'inventaire
     */
    public function cancel(Inventory $inventory)
    {
        try {
            $inventory->cancel();
            return redirect()->route('admin.inventory.index')
                ->with('success', 'Inventaire annulé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rapport d'écarts
     */
    public function report(Inventory $inventory)
    {
        $inventory->load(['shop', 'user', 'validatedBy', 'items.product.category']);

        $items = $inventory->items()
            ->with('product.category')
            ->where('difference', '!=', 0)
            ->orderBy('difference')
            ->get();

        $summary = [
            'total_products' => $inventory->total_products,
            'products_counted' => $inventory->items()->where('counted', true)->count(),
            'products_with_difference' => $items->count(),
            'shortage_count' => $items->where('difference', '<', 0)->count(),
            'shortage_value' => $items->where('difference', '<', 0)->sum('difference_value'),
            'surplus_count' => $items->where('difference', '>', 0)->count(),
            'surplus_value' => $items->where('difference', '>', 0)->sum('difference_value'),
            'total_difference_value' => $inventory->total_difference_value,
        ];

        return view('admin.inventory.report', compact('inventory', 'items', 'summary'));
    }

    /**
     * Imprimer la liste des produits à inventorier
     */
    public function printList(Inventory $inventory)
    {
        $inventory->load(['shop', 'user', 'items.product.category']);

        // Trier les produits par catégorie puis par nom
        $items = $inventory->items()
            ->with('product.category')
            ->get()
            ->sortBy([
                ['product.category.name', 'asc'],
                ['product.name', 'asc'],
            ]);

        // Grouper par catégorie pour faciliter le comptage
        $itemsByCategory = $items->groupBy(function($item) {
            return $item->product->category->name ?? 'Sans catégorie';
        });

        // Infos boutique
        $shopName = $inventory->shop->name ?? Setting::get('shop_name', 'EGREGORE BUSINESS');

        $pdf = Pdf::loadView('admin.inventory.print-list', compact('inventory', 'items', 'itemsByCategory', 'shopName'));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Inventaire_' . $inventory->reference . '_Liste.pdf';

        return $pdf->stream($filename);
    }
}

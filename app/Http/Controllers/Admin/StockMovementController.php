<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockMovementController extends Controller
{
    /**
     * Afficher le journal des mouvements de stock
     */
    public function index(Request $request)
    {
        $query = StockMovement::with(['product.category', 'shop', 'user'])
            ->orderBy('created_at', 'desc');
        
        // Filtres
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        $movements = $query->paginate(50)->withQueryString();
        
        // Données pour les filtres
        $shops = Shop::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $movementTypes = [
            'purchase' => 'Achat fournisseur',
            'sale' => 'Vente',
            'sale_cancel' => 'Annulation vente',
            'return' => 'Retour client',
            'adjustment' => 'Ajustement manuel',
            'inventory' => 'Ajustement inventaire',
            'transfer_in' => 'Transfert entrant',
            'transfer_out' => 'Transfert sortant',
            'entry' => 'Entrée stock',
            'exit' => 'Sortie stock',
            'repair_usage' => 'Utilisation réparation',
            'loss' => 'Perte/Casse',
        ];
        
        // Statistiques de la période filtrée
        $statsQuery = StockMovement::query();
        
        if ($request->filled('shop_id')) {
            $statsQuery->where('shop_id', $request->shop_id);
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        
        $stats = [
            'total_in' => (clone $statsQuery)->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => (clone $statsQuery)->where('quantity', '<', 0)->sum('quantity'),
            'total_movements' => (clone $statsQuery)->count(),
        ];
        
        return view('admin.stock-movements.index', compact(
            'movements', 
            'shops', 
            'products', 
            'movementTypes',
            'stats'
        ));
    }

    /**
     * Afficher les détails d'un mouvement
     */
    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load(['product.category', 'shop', 'user']);
        
        // Récupérer les mouvements avant/après pour le contexte
        $previousMovements = StockMovement::where('product_id', $stockMovement->product_id)
            ->where('shop_id', $stockMovement->shop_id)
            ->where('created_at', '<', $stockMovement->created_at)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        $nextMovements = StockMovement::where('product_id', $stockMovement->product_id)
            ->where('shop_id', $stockMovement->shop_id)
            ->where('created_at', '>', $stockMovement->created_at)
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get();
        
        return view('admin.stock-movements.show', compact(
            'stockMovement',
            'previousMovements',
            'nextMovements'
        ));
    }

    /**
     * Afficher l'historique d'un produit spécifique
     */
    public function productHistory(Request $request, Product $product)
    {
        $query = StockMovement::where('product_id', $product->id)
            ->with(['shop', 'user'])
            ->orderBy('created_at', 'desc');
        
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $movements = $query->paginate(50)->withQueryString();
        $shops = Shop::orderBy('name')->get();
        
        return view('admin.stock-movements.product-history', compact(
            'product',
            'movements',
            'shops'
        ));
    }

    /**
     * Formulaire de correction manuelle de stock
     */
    public function createAdjustment()
    {
        $shops = Shop::orderBy('name')->get();
        $products = Product::with('category')->orderBy('name')->get();
        
        return view('admin.stock-movements.adjustment', compact('shops', 'products'));
    }

    /**
     * Enregistrer une correction manuelle de stock
     */
    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);
        
        $product = Product::findOrFail($request->product_id);
        $currentStock = $product->quantity_in_stock;
        
        // Calculer le nouveau stock et la quantité de mouvement
        switch ($request->adjustment_type) {
            case 'add':
                $movementQuantity = $request->quantity;
                $newStock = $currentStock + $request->quantity;
                break;
            case 'remove':
                $movementQuantity = -$request->quantity;
                $newStock = max(0, $currentStock - $request->quantity);
                break;
            case 'set':
                $movementQuantity = $request->quantity - $currentStock;
                $newStock = $request->quantity;
                break;
        }
        
        // Créer le mouvement de stock
        StockMovement::create([
            'shop_id' => $request->shop_id,
            'product_id' => $request->product_id,
            'user_id' => Auth::id(),
            'type' => 'adjustment',
            'quantity' => $movementQuantity,
            'quantity_before' => $currentStock,
            'quantity_after' => $newStock,
            'reference' => 'ADJ-' . strtoupper(uniqid()),
            'reason' => $request->reason,
        ]);
        
        // Mettre à jour le stock du produit
        $product->update(['quantity_in_stock' => $newStock]);
        
        return redirect()->route('admin.stock-movements.index')
            ->with('success', 'Ajustement de stock enregistré avec succès.');
    }

    /**
     * Export CSV des mouvements
     */
    public function export(Request $request)
    {
        $query = StockMovement::with(['product', 'shop', 'user'])
            ->orderBy('created_at', 'desc');
        
        // Appliquer les mêmes filtres que l'index
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $movements = $query->get();
        
        $filename = 'mouvements-stock-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($movements) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'Date/Heure',
                'Boutique',
                'Produit',
                'SKU',
                'Type',
                'Quantité',
                'Stock avant',
                'Stock après',
                'Référence',
                'Utilisateur',
                'Notes'
            ], ';');
            
            // Données
            $typeLabels = [
                'purchase' => 'Achat fournisseur',
                'sale' => 'Vente',
                'sale_cancel' => 'Annulation vente',
                'return' => 'Retour client',
                'adjustment' => 'Ajustement manuel',
                'inventory' => 'Ajustement inventaire',
                'transfer_in' => 'Transfert entrant',
                'transfer_out' => 'Transfert sortant',
                'initial' => 'Stock initial',
                'loss' => 'Perte/Casse',
            ];
            
            foreach ($movements as $movement) {
                fputcsv($file, [
                    $movement->created_at->format('d/m/Y H:i:s'),
                    $movement->shop->name ?? '-',
                    $movement->product->name ?? '-',
                    $movement->product->sku ?? '-',
                    $typeLabels[$movement->type] ?? $movement->type,
                    $movement->quantity,
                    $movement->quantity_before,
                    $movement->quantity_after,
                    $movement->reference ?? '-',
                    $movement->user->name ?? '-',
                    $movement->reason ?? '-'
                ], ';');
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}

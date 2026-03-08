<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use App\Models\SavTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Contrôleur pour la gestion des boutiques (multi-tenant)
 * Le middleware est géré dans les routes (routes/web.php)
 */
class ShopController extends Controller
{
    /**
     * Liste des boutiques
     */
    public function index()
    {
        $shops = Shop::withCount(['users', 'products', 'sales', 'repairs'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.shops.index', compact('shops'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('admin.shops.create');
    }

    /**
     * Enregistrer une nouvelle boutique
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:shops,code',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Générer un code automatique si non fourni
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateShopCode($validated['name']);
        }

        $shop = Shop::create($validated);

        return redirect()
            ->route('admin.shops.show', $shop)
            ->with('success', "Boutique '{$shop->name}' créée avec succès.");
    }

    /**
     * Afficher une boutique
     */
    public function show(Shop $shop)
    {
        $shop->load(['users', 'products' => fn($q) => $q->limit(10)]);
        $stats = $shop->getStats();

        // Utilisateurs sans boutique (pour assignation)
        $availableUsers = User::whereNull('shop_id')
            ->where('id', '!=', auth()->id()) // Exclure l'admin actuel
            ->get();

        return view('admin.shops.show', compact('shop', 'stats', 'availableUsers'));
    }

    /**
     * Formulaire de modification
     */
    public function edit(Shop $shop)
    {
        return view('admin.shops.edit', compact('shop'));
    }

    /**
     * Mettre à jour une boutique
     */
    public function update(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:shops,code,' . $shop->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $shop->update($validated);

        return redirect()
            ->route('admin.shops.show', $shop)
            ->with('success', 'Boutique mise à jour avec succès.');
    }

    /**
     * Supprimer une boutique
     */
    public function destroy(Shop $shop)
    {
        // Vérifier qu'il n'y a plus d'utilisateurs
        if ($shop->users()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une boutique avec des utilisateurs assignés.');
        }

        $shop->delete();

        return redirect()
            ->route('admin.shops.index')
            ->with('success', 'Boutique supprimée avec succès.');
    }

    /**
     * Assigner un utilisateur à une boutique
     */
    public function assignUser(Request $request, Shop $shop)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        
        // Les admins ne peuvent pas être assignés à une boutique
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Les administrateurs ne peuvent pas être assignés à une boutique.');
        }

        $user->update(['shop_id' => $shop->id]);

        return back()->with('success', "'{$user->name}' assigné à '{$shop->name}' avec succès.");
    }

    /**
     * Retirer un utilisateur d'une boutique
     */
    public function removeUser(Shop $shop, User $user)
    {
        if ($user->shop_id !== $shop->id) {
            return back()->with('error', 'Cet utilisateur n\'appartient pas à cette boutique.');
        }

        $user->update(['shop_id' => null]);

        return back()->with('success', "'{$user->name}' retiré de la boutique.");
    }

    /**
     * Activer/désactiver une boutique
     */
    public function toggleStatus(Shop $shop)
    {
        $shop->update(['is_active' => !$shop->is_active]);

        $status = $shop->is_active ? 'activée' : 'désactivée';
        return back()->with('success', "Boutique {$status}.");
    }

    /**
     * Tableau de bord multi-boutiques
     */
    public function dashboard()
    {
        $shops = Shop::active()
            ->withCount(['sales', 'repairs'])
            ->get()
            ->map(function ($shop) {
                // CA du jour
                $shop->today_revenue = $shop->sales()
                    ->whereDate('created_at', today())
                    ->sum('total_amount');
                
                // Calcul du bénéfice: CA - (Prix d'achat + Dépenses + SAV produit)
                $todaySales = $shop->sales()
                    ->whereDate('created_at', today())
                    ->with('items.product')
                    ->get();
                
                // 1. Coût d'achat des produits vendus
                $purchaseCost = $todaySales->sum(function ($sale) {
                    return $sale->items->sum(function ($item) {
                        $purchasePrice = $item->product->purchase_price ?? 0;
                        return $purchasePrice * $item->quantity;
                    });
                });
                
                // 2. Dépenses du jour
                $todayExpenses = $shop->expenses()
                    ->whereDate('expense_date', today())
                    ->where('status', 'approved')
                    ->sum('amount');
                
                // 3. Coût SAV produit du jour (hors garantie réparation)
                $todaySavCost = SavTicket::where('shop_id', $shop->id)
                    ->whereDate('created_at', today())
                    ->where('type', '!=', 'repair_warranty')
                    ->whereIn('status', ['resolved', 'closed'])
                    ->sum('refund_amount');
                
                // Bénéfice = CA - (Coût d'achat + Dépenses + SAV)
                $shop->today_profit = $shop->today_revenue - ($purchaseCost + $todayExpenses + $todaySavCost);
                
                $shop->pending_repairs = $shop->repairs()
                    ->whereNotIn('status', ['delivered', 'cancelled'])
                    ->count();
                return $shop;
            });

        $totals = [
            'revenue_today' => $shops->sum('today_revenue'),
            'profit_today' => $shops->sum('today_profit'),
            'sales_today' => $shops->sum('sales_count'),
            'pending_repairs' => $shops->sum('pending_repairs'),
            'shops_count' => $shops->count(),
        ];

        return view('admin.shops.dashboard', compact('shops', 'totals'));
    }

    /**
     * Générer un code boutique unique
     */
    private function generateShopCode(string $name): string
    {
        $base = strtoupper(Str::substr(Str::slug($name, ''), 0, 3));
        $counter = 1;
        $code = $base . $counter;

        while (Shop::where('code', $code)->exists()) {
            $counter++;
            $code = $base . $counter;
        }

        return $code;
    }
}

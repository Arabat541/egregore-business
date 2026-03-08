<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * Gestion des catégories - Admin uniquement
 * Les catégories peuvent être globales ou spécifiques à une boutique
 */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->get('shop_id');
        $shops = Shop::active()->orderBy('name')->get();
        
        $query = Category::with('parent', 'children', 'shop')
            ->withCount('products')
            ->ordered();
        
        if ($shopId === 'global') {
            // Catégories globales uniquement
            $query->global();
        } elseif ($shopId) {
            // Catégories d'une boutique spécifique (inclut globales)
            $query->forShop($shopId);
        }
        
        $categories = $query->paginate(20);

        return view('admin.categories.index', compact('categories', 'shops', 'shopId'));
    }

    public function create()
    {
        $parentCategories = Category::parents()->active()->ordered()->get();
        $shops = Shop::active()->orderBy('name')->get();
        
        return view('admin.categories.create', compact('parentCategories', 'shops'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'shop_id' => 'nullable|exists:shops,id',
            'is_global' => 'boolean',
        ]);

        // Si pas de shop_id, c'est une catégorie globale
        if (empty($validated['shop_id'])) {
            $validated['is_global'] = true;
        } else {
            $validated['is_global'] = $request->boolean('is_global');
        }

        $category = Category::create($validated);

        ActivityLog::log('create', $category, null, $category->toArray(), "Création catégorie: {$category->name}");

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::parents()
            ->where('id', '!=', $category->id)
            ->active()
            ->ordered()
            ->get();
        
        $shops = Shop::active()->orderBy('name')->get();

        return view('admin.categories.edit', compact('category', 'parentCategories', 'shops'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'shop_id' => 'nullable|exists:shops,id',
            'is_global' => 'boolean',
        ]);

        // Empêcher de se mettre soi-même comme parent
        if ($validated['parent_id'] == $category->id) {
            return back()->with('error', 'Une catégorie ne peut pas être son propre parent.');
        }

        // Si pas de shop_id, c'est une catégorie globale
        if (empty($validated['shop_id'])) {
            $validated['is_global'] = true;
            $validated['shop_id'] = null;
        } else {
            $validated['is_global'] = $request->boolean('is_global');
        }

        $oldValues = $category->toArray();
        $category->update($validated);

        ActivityLog::log('update', $category, $oldValues, $category->toArray(), "Modification catégorie: {$category->name}");

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(Category $category)
    {
        // Vérifier si la catégorie a des produits
        if ($category->products()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette catégorie car elle contient des produits.');
        }

        // Vérifier si la catégorie a des sous-catégories
        if ($category->children()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette catégorie car elle a des sous-catégories.');
        }

        ActivityLog::log('delete', $category, $category->toArray(), null, "Suppression catégorie: {$category->name}");

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Setting;

class StorefrontController extends Controller
{
    public function home()
    {
        $shops = Shop::where('is_active', true)->get();

        $featuredProducts = Product::with('category', 'shop')
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $categories = Category::withCount(['products' => function ($q) {
                $q->where('is_active', true)->where('quantity_in_stock', '>', 0);
            }])
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($c) => $c->products_count > 0)
            ->groupBy('slug')
            ->map(function ($group) {
                $first = $group->first();
                $first->products_count = $group->sum('products_count');
                return $first;
            })
            ->values();

        $companyName = Setting::get('company_name', 'EGREGORE BUSINESS');

        return view('storefront.home', compact('shops', 'featuredProducts', 'categories', 'companyName'));
    }

    public function catalog()
    {
        $query = Product::with('category', 'shop')
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0);

        if (request('category')) {
            // Trouver toutes les catégories avec le même slug (multi-boutiques)
            $matchingCategories = Category::where('slug', request('category'))->pluck('id');
            $categoryIds = $matchingCategories;
            // Inclure les sous-catégories
            $childIds = Category::whereIn('parent_id', $matchingCategories)->pluck('id');
            $categoryIds = $categoryIds->merge($childIds);
            $query->whereIn('category_id', $categoryIds);
        }

        if (request('shop')) {
            $query->where('shop_id', request('shop'));
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if (request('brand')) {
            $query->where('brand', request('brand'));
        }

        $sort = request('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('normal_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('normal_price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $products = $query->paginate(24)->appends(request()->query());

        $categories = Category::withCount(['products' => function ($q) {
                $q->where('is_active', true)->where('quantity_in_stock', '>', 0);
            }])
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($c) => $c->products_count > 0)
            ->groupBy('slug')
            ->map(function ($group) {
                $first = $group->first();
                $first->products_count = $group->sum('products_count');
                return $first;
            })
            ->values();

        $shops = Shop::where('is_active', true)->get();

        $brands = Product::where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort();

        $companyName = Setting::get('company_name', 'EGREGORE BUSINESS');

        return view('storefront.catalog', compact('products', 'categories', 'shops', 'brands', 'companyName'));
    }

    public function product($id)
    {
        $product = Product::with('category', 'shop')
            ->where('is_active', true)
            ->findOrFail($id);

        $relatedProducts = Product::with('category', 'shop')
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->limit(4)
            ->get();

        $companyName = Setting::get('company_name', 'EGREGORE BUSINESS');

        return view('storefront.product', compact('product', 'relatedProducts', 'companyName'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Produits — lecture seule pour l'API mobile
 */
class ProductController extends Controller
{
    /**
     * GET /api/products?search=&category_id=&in_stock=1&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')->active();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('in_stock')) {
            $query->where('quantity_in_stock', '>', 0);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $products = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data'  => $products->map(fn(Product $p) => $this->formatProduct($p)),
            'meta'  => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('category');
        return response()->json($this->formatProduct($product));
    }

    private function formatProduct(Product $p): array
    {
        return [
            'id'                => $p->id,
            'name'              => $p->name,
            'sku'               => $p->sku,
            'category'          => $p->category?->name,
            'brand'             => $p->brand,
            'model'             => $p->model,
            'quantity_in_stock' => $p->quantity_in_stock,
            'normal_price'      => (float) $p->normal_price,
            'reseller_price'    => (float) ($p->reseller_price ?? 0),
            'image_url'         => $p->image ? asset('storage/' . $p->image) : null,
        ];
    }
}

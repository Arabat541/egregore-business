<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $item) {
            $product = Product::with('shop')->find($productId);
            if ($product && $product->is_active) {
                $qty = min($item['quantity'], $product->quantity_in_stock);
                $lineTotal = $product->normal_price * $qty;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
                $total += $lineTotal;
            }
        }

        return view('storefront.cart', compact('cartItems', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->is_active || $product->quantity_in_stock < 1) {
            return back()->with('error', 'Ce produit n\'est plus disponible.');
        }

        $cart = session()->get('cart', []);
        $currentQty = $cart[$product->id]['quantity'] ?? 0;
        $newQty = $currentQty + $request->quantity;

        if ($newQty > $product->quantity_in_stock) {
            return back()->with('error', "Stock insuffisant. Disponible: {$product->quantity_in_stock}");
        }

        $cart[$product->id] = [
            'quantity' => $newQty,
            'shop_id' => $product->shop_id,
        ];

        session()->put('cart', $cart);

        return back()->with('success', 'Produit ajouté au panier.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = session()->get('cart', []);

        if ($request->quantity <= 0) {
            unset($cart[$request->product_id]);
        } else {
            $product = Product::findOrFail($request->product_id);
            if ($request->quantity > $product->quantity_in_stock) {
                return back()->with('error', "Stock insuffisant. Disponible: {$product->quantity_in_stock}");
            }
            $cart[$request->product_id]['quantity'] = $request->quantity;
        }

        session()->put('cart', $cart);

        return back()->with('success', 'Panier mis à jour.');
    }

    public function remove(Request $request)
    {
        $cart = session()->get('cart', []);
        unset($cart[$request->product_id]);
        session()->put('cart', $cart);

        return back()->with('success', 'Produit retiré du panier.');
    }

    public function clear()
    {
        session()->forget('cart');

        return back()->with('success', 'Panier vidé.');
    }
}

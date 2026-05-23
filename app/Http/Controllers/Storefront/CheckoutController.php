<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use App\Models\OnlineOrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('storefront.cart')->with('error', 'Votre panier est vide.');
        }

        $cartItems = [];
        $total = 0;
        $shopIds = [];

        foreach ($cart as $productId => $item) {
            $product = Product::with('shop')->find($productId);
            if ($product && $product->is_active && $product->quantity_in_stock >= $item['quantity']) {
                $lineTotal = $product->normal_price * $item['quantity'];
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
                $total += $lineTotal;
                $shopIds[$product->shop_id] = true;
            }
        }

        if (empty($cartItems)) {
            session()->forget('cart');
            return redirect()->route('storefront.cart')->with('error', 'Les produits ne sont plus disponibles.');
        }

        $shops = Shop::whereIn('id', array_keys($shopIds))->get();
        $companyName = Setting::get('company_name', 'EGREGORE BUSINESS');

        return view('storefront.checkout', compact('cartItems', 'total', 'shops', 'companyName'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string|max:500',
            'customer_city' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery,mobile_money,bank_transfer',
            'delivery_method' => 'required|in:pickup,delivery',
            'notes' => 'nullable|string|max:1000',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('storefront.cart')->with('error', 'Votre panier est vide.');
        }

        // Group cart items by shop
        $shopGroups = [];
        foreach ($cart as $productId => $item) {
            $shopId = $item['shop_id'];
            $shopGroups[$shopId][$productId] = $item;
        }

        $orderTokens = [];

        DB::beginTransaction();
        try {
            foreach ($shopGroups as $shopId => $items) {
                $subtotal   = 0;
                $orderItems = [];
                $lockedProducts = [];

                // Phase 1 : verrouillage, validation et collecte des données
                foreach ($items as $productId => $item) {
                    $product = Product::lockForUpdate()->find($productId);

                    if (!$product || !$product->is_active || $product->quantity_in_stock < $item['quantity']) {
                        DB::rollBack();
                        return redirect()->route('storefront.cart')
                            ->with('error', "Le produit \"{$product?->name}\" n'est plus disponible en quantité suffisante.");
                    }

                    $lineTotal  = $product->normal_price * $item['quantity'];
                    $subtotal  += $lineTotal;

                    $orderItems[]    = [
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $product->normal_price,
                        'total_price'  => $lineTotal,
                    ];
                    $lockedProducts[] = ['model' => $product, 'quantity' => $item['quantity']];
                }

                // Phase 2 : création de la commande (on a maintenant l'ID pour le mouvement de stock)
                $order = OnlineOrder::create([
                    'shop_id'          => $shopId,
                    'customer_name'    => $request->customer_name,
                    'customer_phone'   => $request->customer_phone,
                    'customer_email'   => $request->customer_email,
                    'customer_address' => $request->customer_address,
                    'customer_city'    => $request->customer_city,
                    'subtotal'         => $subtotal,
                    'shipping_cost'    => 0,
                    'total_amount'     => $subtotal,
                    'payment_method'   => $request->payment_method,
                    'delivery_method'  => $request->delivery_method,
                    'notes'            => $request->notes,
                ]);

                foreach ($orderItems as $itemData) {
                    $order->items()->create($itemData);
                }

                // Phase 3 : décrémentation du stock + historique (recordExit gère les deux)
                foreach ($lockedProducts as $entry) {
                    StockMovement::recordExit(
                        $entry['model'],
                        null,
                        $entry['quantity'],
                        $order,
                        'online_sale'
                    );
                }

                $orderTokens[] = $order->confirmation_token;
            }

            DB::commit();
            session()->forget('cart');

            return redirect()->route('storefront.order.confirmation', ['token' => $orderTokens[0]])
                ->with('success', 'Votre commande a été enregistrée avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    public function confirmation(string $token)
    {
        $order = OnlineOrder::with('items.product', 'shop')
            ->where('confirmation_token', $token)
            ->firstOrFail();

        $companyName = Setting::get('company_name', 'EGREGORE BUSINESS');

        return view('storefront.confirmation', compact('order', 'companyName'));
    }

    public function track()
    {
        return view('storefront.track');
    }

    public function trackOrder(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
        ]);

        $order = OnlineOrder::with('items.product', 'shop')
            ->where('order_number', $request->order_number)
            ->first();

        if (!$order) {
            return back()->with('error', 'Aucune commande trouvée avec ce numéro.');
        }

        $companyName = Setting::get('company_name', 'EGREGORE BUSINESS');

        return view('storefront.track-result', compact('order', 'companyName'));
    }
}

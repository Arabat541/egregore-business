<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrder;
use Illuminate\Http\Request;

class OnlineOrderController extends Controller
{
    public function index()
    {
        $query = OnlineOrder::withoutGlobalScope('shop')->with('shop', 'items')
            ->orderByDesc('created_at');

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->appends(request()->query());

        $statusCounts = OnlineOrder::withoutGlobalScope('shop')->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.online-orders.index', compact('orders', 'statusCounts'));
    }

    public function show(OnlineOrder $onlineOrder)
    {
        $onlineOrder->load('items.product', 'shop', 'processedBy');

        return view('admin.online-orders.show', compact('onlineOrder'));
    }

    public function updateStatus(Request $request, OnlineOrder $onlineOrder)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,ready,shipped,delivered,cancelled',
        ]);

        $newStatus = $request->status;
        $onlineOrder->status = $newStatus;
        $onlineOrder->processed_by = auth()->id();

        if ($newStatus === 'confirmed' && !$onlineOrder->confirmed_at) {
            $onlineOrder->confirmed_at = now();
        }
        if ($newStatus === 'shipped' && !$onlineOrder->shipped_at) {
            $onlineOrder->shipped_at = now();
        }
        if ($newStatus === 'delivered' && !$onlineOrder->delivered_at) {
            $onlineOrder->delivered_at = now();
        }

        // If cancelled, restore stock
        if ($newStatus === 'cancelled') {
            foreach ($onlineOrder->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity_in_stock', $item->quantity);
                }
            }
        }

        $onlineOrder->save();

        return back()->with('success', 'Statut mis à jour avec succès.');
    }

    public function updatePayment(Request $request, OnlineOrder $onlineOrder)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,refunded',
        ]);

        $onlineOrder->payment_status = $request->payment_status;
        $onlineOrder->save();

        return back()->with('success', 'Statut de paiement mis à jour.');
    }

    public function addNote(Request $request, OnlineOrder $onlineOrder)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        $onlineOrder->admin_notes = $request->admin_notes;
        $onlineOrder->save();

        return back()->with('success', 'Note ajoutée.');
    }
}

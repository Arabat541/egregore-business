<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recherche globale (Ctrl+K) — JSON uniquement
 * Recherche simultanée dans produits, clients, réparations et factures.
 */
class GlobalSearchController extends Controller
{
    private const MAX_PER_GROUP = 5;
    private const MIN_QUERY_LENGTH = 2;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (mb_strlen($q) < self::MIN_QUERY_LENGTH) {
            return response()->json(['results' => []]);
        }

        $results = [];
        $user    = auth()->user();

        // ── Produits (admin + caissière) ────────────────────────────────
        if ($user->hasAnyRole(['admin', 'caissiere'])) {
            Product::search($q)
                ->active()
                ->with('category')
                ->limit(self::MAX_PER_GROUP)
                ->get()
                ->each(function (Product $p) use (&$results, $user) {
                    $threshold = (int) ($p->stock_alert_threshold ?? 5);
                    $stock     = (int) $p->quantity_in_stock;
                    $stockStatus = $stock <= 0 ? 'danger' : ($stock <= $threshold ? 'warning' : 'success');

                    $results[] = [
                        'group'       => 'Produits',
                        'icon'        => 'bi-box-seam',
                        'label'       => $p->name,
                        'sublabel'    => $p->category?->name . ($p->brand ? ' · ' . $p->brand : ''),
                        'sku'         => $p->sku,
                        'price'       => number_format((float) ($p->reseller_price ?? $p->normal_price), 0, ',', ' ') . ' FCFA',
                        'stock'       => $stock,
                        'stockStatus' => $stockStatus,
                        'purchase'    => $user->hasRole('admin')
                            ? number_format((float) $p->purchase_price, 0, ',', ' ') . ' FCFA'
                            : null,
                        'url'         => $this->productUrl($p),
                    ];
                });
        }

        // ── Clients (admin + caissière) ─────────────────────────────────
        if ($user->hasAnyRole(['admin', 'caissiere'])) {
            Customer::search($q)
                ->limit(self::MAX_PER_GROUP)
                ->get()
                ->each(function (Customer $c) use (&$results) {
                    $results[] = [
                        'group'    => 'Clients',
                        'icon'     => 'bi-person',
                        'label'    => $c->full_name,
                        'sublabel' => $c->phone,
                        'url'      => $this->customerUrl($c),
                    ];
                });
        }

        // ── Réparations (admin + caissière + technicien) ────────────────
        if ($user->hasAnyRole(['admin', 'caissiere', 'technicien'])) {
            Repair::search($q)
                ->with('customer')
                ->limit(self::MAX_PER_GROUP)
                ->get()
                ->each(function (Repair $r) use (&$results, $user) {
                    $results[] = [
                        'group'    => 'Réparations',
                        'icon'     => 'bi-tools',
                        'label'    => $r->repair_number . ' — ' . $r->device_brand . ' ' . $r->device_model,
                        'sublabel' => $r->customer?->full_name,
                        'url'      => $this->repairUrl($r, $user),
                    ];
                });
        }

        // ── Factures (caissière uniquement — les admins n'ont pas de vue individuelle) ──
        if ($user->hasRole('caissiere')) {
            Sale::where('invoice_number', 'like', "%{$q}%")
                ->orWhereHas('customer', fn($sq) => $sq->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%"))
                ->limit(self::MAX_PER_GROUP)
                ->get()
                ->each(function (Sale $s) use (&$results) {
                    $results[] = [
                        'group'    => 'Factures',
                        'icon'     => 'bi-receipt',
                        'label'    => $s->invoice_number,
                        'sublabel' => number_format((float) $s->total_amount, 0, ',', ' ') . ' FCFA — ' . $s->created_at->format('d/m/Y'),
                        'url'      => route('cashier.sales.show', $s),
                    ];
                });
        }

        return response()->json(['results' => $results]);
    }

    private function productUrl(Product $product): string
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return route('admin.products.edit', $product);
        }
        // caissière : redirige vers la création de vente (le produit sera pré-recherché)
        return route('cashier.sales.create') . '?sku=' . urlencode((string) ($product->sku ?? ''));
    }

    private function customerUrl(Customer $customer): string
    {
        if (auth()->user()->hasRole('admin')) {
            return route('admin.reports.customers') . '?search=' . urlencode($customer->full_name);
        }
        return route('cashier.customers.show', $customer);
    }

    private function repairUrl(Repair $repair, $user): string
    {
        if ($user->hasRole('technicien')) {
            return route('technician.repairs.show', $repair);
        }
        return route('cashier.repairs.show', $repair);
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Shop;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Vérifie les produits en stock bas par boutique et envoie des notifications
 * Conçu pour s'exécuter fréquemment (toutes les 2h) en évitant les doublons.
 */
class CheckLowStockCommand extends Command
{
    protected $signature = 'app:check-low-stock {--force : Ignorer la déduplication}';
    protected $description = 'Vérifie les produits en stock bas et notifie les admins (par boutique)';

    public function handle(NotificationService $notifications): int
    {
        $threshold     = (int) Setting::get('low_stock_threshold', 5);
        $force         = $this->option('force');
        $totalCritical = 0;
        $totalLow      = 0;

        Shop::where('is_active', true)->each(function ($shop) use (
            $notifications, $threshold, $force, &$totalCritical, &$totalLow
        ) {
            // Produits épuisés pour cette boutique
            $critical = Product::where('shop_id', $shop->id)
                ->where('quantity_in_stock', '<=', 0)
                ->where('is_active', true)
                ->get();

            // Produits en stock bas pour cette boutique
            $low = Product::where('shop_id', $shop->id)
                ->where('quantity_in_stock', '>', 0)
                ->where('quantity_in_stock', '<=', $threshold)
                ->where('is_active', true)
                ->get();

            foreach ($critical as $product) {
                $cacheKey = "notif_stock_critical_{$product->id}";
                if (!$force && Cache::has($cacheKey)) {
                    continue;
                }
                $notifications->stockCritical($product);
                Cache::put($cacheKey, true, now()->addHours(6));
            }

            foreach ($low as $product) {
                $cacheKey = "notif_stock_low_{$product->id}";
                if (!$force && Cache::has($cacheKey)) {
                    continue;
                }
                $notifications->stockLow($product);
                Cache::put($cacheKey, true, now()->addHours(2));
            }

            $totalCritical += $critical->count();
            $totalLow      += $low->count();
        });

        if ($totalCritical === 0 && $totalLow === 0) {
            $this->info('Aucun produit en stock bas.');
            return Command::SUCCESS;
        }

        $this->info("Stock bas: {$totalLow} produit(s) | Stock critique: {$totalCritical} produit(s)");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\Product;
use App\Services\NotificationService;

class ProductObserver
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     * Vérifier le stock et envoyer des notifications si nécessaire
     */
    public function updated(Product $product): void
    {
        // Vérifier si le stock a changé
        if ($product->isDirty('quantity_in_stock') || $product->wasChanged('quantity_in_stock')) {
            $this->checkStockLevel($product);
        }
    }

    /**
     * Vérifier le niveau de stock et notifier si nécessaire
     */
    protected function checkStockLevel(Product $product): void
    {
        $threshold = $product->stock_alert_threshold ?? 5;
        $stock = $product->quantity_in_stock;

        // Stock critique (0 ou négatif)
        if ($stock <= 0) {
            $this->notificationService->notifyRole(
                'admin',
                Notification::TYPE_STOCK_CRITICAL,
                '🚨 Rupture de stock !',
                "{$product->name} - Stock épuisé !",
                route('admin.products.edit', $product),
                $product
            );
        }
        // Stock bas (en dessous du seuil)
        elseif ($stock <= $threshold && $stock > 0) {
            // Éviter les notifications en double : vérifier si une notification récente existe
            $recentNotification = Notification::where('notifiable_type', Product::class)
                ->where('notifiable_id', $product->id)
                ->where('type', Notification::TYPE_STOCK_LOW)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if (!$recentNotification) {
                $this->notificationService->notifyRole(
                    'admin',
                    Notification::TYPE_STOCK_LOW,
                    '⚠️ Stock bas',
                    "{$product->name} - Stock: {$stock} (Seuil: {$threshold})",
                    route('admin.products.edit', $product),
                    $product
                );
            }
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}

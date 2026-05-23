<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\RepairStatusChanged;
use App\Events\SaleCompleted;
use App\Listeners\CheckLowStockAfterSale;
use App\Listeners\NotifyRepairStatusChange;
use App\Models\Product;
use App\Models\Shop;
use App\Observers\ProductObserver;
use App\Observers\ShopObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pagination Bootstrap 5
        Paginator::useBootstrapFive();

        // Enregistrer les observers
        Product::observe(ProductObserver::class);
        Shop::observe(ShopObserver::class);

        // Enregistrer les événements métier
        Event::listen(SaleCompleted::class, CheckLowStockAfterSale::class);
        Event::listen(RepairStatusChanged::class, NotifyRepairStatusChange::class);
    }
}

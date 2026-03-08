<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Shop;
use App\Observers\ProductObserver;
use App\Observers\ShopObserver;
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
        // Enregistrer les observers
        Product::observe(ProductObserver::class);
        Shop::observe(ShopObserver::class);
    }
}

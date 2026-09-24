<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Observers\InventoryLogObserver;
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
        Product::observe(InventoryLogObserver::class);
        Category::observe(InventoryLogObserver::class);
    }
}

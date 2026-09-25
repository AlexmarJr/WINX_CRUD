<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Observers\CategorySearchObserver;
use App\Observers\InventoryLogObserver;
use App\Observers\ProductSearchObserver;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);

        Product::observe(InventoryLogObserver::class);
        Product::observe(ProductSearchObserver::class);
        Category::observe(InventoryLogObserver::class);
        Category::observe(CategorySearchObserver::class);
    }
}

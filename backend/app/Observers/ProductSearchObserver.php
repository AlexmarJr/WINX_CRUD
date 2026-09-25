<?php

namespace App\Observers;

use App\Jobs\SyncProductToSearch;
use App\Models\Product;

class ProductSearchObserver
{
    public function created(Product $product): void
    {
        $this->dispatch($product);
    }

    public function updated(Product $product): void
    {
        $this->dispatch($product);
    }

    public function deleted(Product $product): void
    {
        $this->dispatch($product);
    }

    public function restored(Product $product): void
    {
        $this->dispatch($product);
    }

    private function dispatch(Product $product): void
    {
        if (config('search.driver') === 'elasticsearch') {
            SyncProductToSearch::dispatch($product->id)->onQueue('search')->afterCommit();
        }
    }
}

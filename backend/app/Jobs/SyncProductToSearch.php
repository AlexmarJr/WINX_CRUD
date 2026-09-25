<?php

namespace App\Jobs;

use App\Models\Product;
use App\Repositories\ProductSearchRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductToSearch implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public string $productId) {}

    public function handle(ProductSearchRepository $searchRepository): void
    {
        $product = Product::withTrashed()->with('category')->find($this->productId);

        if ($product === null || $product->trashed()) {
            $searchRepository->remove($this->productId);

            return;
        }

        $searchRepository->index($product);
    }
}

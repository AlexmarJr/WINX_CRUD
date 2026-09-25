<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCategoryProductsToSearch implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $categoryId) {}

    public function handle(): void
    {
        Product::query()->where('category_id', $this->categoryId)->select('id')->chunkById(500, function ($products): void {
            foreach ($products as $product) {
                SyncProductToSearch::dispatch($product->id)->onQueue('search');
            }
        });
    }
}

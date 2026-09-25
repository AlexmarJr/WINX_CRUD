<?php

namespace App\Observers;

use App\Jobs\SyncCategoryProductsToSearch;
use App\Models\Category;

class CategorySearchObserver
{
    public function updated(Category $category): void
    {
        if (config('search.driver') === 'elasticsearch' && $category->wasChanged('name')) {
            SyncCategoryProductsToSearch::dispatch($category->id)->onQueue('search')->afterCommit();
        }
    }
}

<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AiProductRepository
{
    /** @return Collection<int, Product> */
    public function forUser(User $actor): Collection
    {
        return Product::query()
            ->where('tenancy_id', $actor->tenancy_id)
            ->where('user_id', $actor->id)
            ->orderBy('name')
            ->get(['name', 'cost', 'price', 'stock']);
    }
}

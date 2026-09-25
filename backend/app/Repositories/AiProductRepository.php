<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class AiProductRepository
{
    /** @return Collection<int, Product> */
    public function forTenancy(string $tenancyId): Collection
    {
        return Product::query()
            ->where('tenancy_id', $tenancyId)
            ->orderBy('name')
            ->get(['name', 'cost', 'price', 'stock']);
    }
}

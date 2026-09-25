<?php

namespace App\Repositories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function maxPriceForTenancy(string $tenancyId): string
    {
        return number_format((float) (Product::query()->where('tenancy_id', $tenancyId)->max('price') ?? 0), 2, '.', '');
    }

    /** @return array<string, mixed> */
    public function summaryForTenancy(string $tenancyId): array
    {
        $totals = Product::query()
            ->where('tenancy_id', $tenancyId)
            ->selectRaw('COUNT(*) AS product_count, COALESCE(SUM(stock), 0) AS total_units, COALESCE(SUM(stock * COALESCE(cost, 0)), 0) AS purchase_total, COALESCE(SUM(stock * price), 0) AS resale_total')
            ->first();

        $lowStock = Product::query()
            ->with('category')
            ->where('tenancy_id', $tenancyId)
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
                'stock' => $product->stock,
            ])
            ->all();

        return [
            'product_count' => (int) $totals->product_count,
            'total_units' => (int) $totals->total_units,
            'purchase_total' => (string) $totals->purchase_total,
            'resale_total' => (string) $totals->resale_total,
            'low_stock' => $lowStock,
        ];
    }

    public function paginateForTenancy(string $tenancyId, ?string $search, ?ProductStatus $status, ?string $categoryId, ?string $availability, ?string $minPrice, ?string $maxPrice, int $perPage, string $sortBy, string $sortDir): LengthAwarePaginator
    {
        $query = Product::query()
            ->select('products.*')
            ->with('category')
            ->where('products.tenancy_id', $tenancyId)
            ->when($search, fn ($query) => $query->whereRaw('LOWER(products.name) LIKE LOWER(?)', ["%{$search}%"]))
            ->when($status, fn ($query) => $query->where('products.status', $status->value))
            ->when($categoryId, fn ($query) => $query->where('products.category_id', $categoryId))
            ->when($availability === 'in_stock', fn ($query) => $query->where('products.stock', '>', 0))
            ->when($availability === 'out_of_stock', fn ($query) => $query->where('products.stock', 0))
            ->when($minPrice !== null, fn ($query) => $query->where('products.price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($query) => $query->where('products.price', '<=', $maxPrice));

        if ($sortBy === 'category') {
            $query->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->orderBy('categories.name', $sortDir);
        } else {
            $query->orderBy('products.'.$sortBy, $sortDir);
        }

        return $query->orderBy('products.id', 'desc')->paginate($perPage);
    }

    /** @param array<int, string> $ids */
    public function findManyForTenancy(string $tenancyId, array $ids): Collection
    {
        return Product::query()->with('category')->where('tenancy_id', $tenancyId)->whereIn('id', $ids)->get();
    }

    /** @return array<int, string> */
    public function suggestNamesForTenancy(string $tenancyId, string $query): array
    {
        return Product::query()
            ->where('tenancy_id', $tenancyId)
            ->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$query}%"])
            ->distinct()
            ->orderBy('name')
            ->limit(5)
            ->pluck('name')
            ->all();
    }

    public function findForTenancyOrFail(string $tenancyId, string $id): Product
    {
        return Product::query()->with('category')->where('tenancy_id', $tenancyId)->findOrFail($id);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes)->load('category');
    }

    /** @param array<string, mixed> $attributes */
    public function update(Product $product, array $attributes): Product
    {
        $product->fill($attributes);
        $product->save();

        return $product->refresh()->load('category');
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}

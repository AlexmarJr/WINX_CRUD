<?php

namespace App\Repositories;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function paginateForTenancy(string $tenancyId, ?string $search, ?CategoryStatus $status, int $perPage, string $sortBy, string $sortDir): LengthAwarePaginator
    {
        $query = Category::query()
            ->withCount('products')
            ->where('tenancy_id', $tenancyId)
            ->when($search, fn ($query) => $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$search}%"]))
            ->when($status, fn ($query) => $query->where('status', $status->value));

        return $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findForTenancyOrFail(string $tenancyId, string $id): Category
    {
        return Category::query()->withCount('products')->where('tenancy_id', $tenancyId)->findOrFail($id);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Category $category, array $attributes): Category
    {
        $category->fill($attributes);
        $category->save();

        return $category->refresh();
    }

    public function hasProducts(Category $category): bool
    {
        return $category->products()->exists();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}

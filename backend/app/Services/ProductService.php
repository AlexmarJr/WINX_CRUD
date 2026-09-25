<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use App\Repositories\ProductSearchRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;

class ProductService
{
    public function __construct(
        private ProductRepository $productsRepository,
        private ProductSearchRepository $productSearchRepository,
    ) {}

    public function maxPrice(User $actor): string
    {
        return $this->productsRepository->maxPriceForTenancy($this->tenancyId($actor));
    }

    /** @return array<string, mixed> */
    public function summary(User $actor): array
    {
        return $this->productsRepository->summaryForTenancy($this->tenancyId($actor));
    }

    /** @param array<string, mixed> $filters */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        if (config('search.driver') === 'elasticsearch' && filled($filters['search'] ?? null)) {
            $tenancyId = $this->tenancyId($actor);
            $page = (int) ($filters['page'] ?? 1);
            $perPage = (int) ($filters['per_page'] ?? 15);
            $result = $this->productSearchRepository->search($tenancyId, trim($filters['search']), $filters, $page, $perPage);
            $products = $this->productsRepository->findManyForTenancy($tenancyId, $result['ids'])->keyBy('id');
            $ordered = collect($result['ids'])->map(fn (string $id): ?Product => $products->get($id))->filter()->values();

            return new ConcretePaginator($ordered, $result['total'], $perPage, $page, [
                'path' => ConcretePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]);
        }

        return $this->productsRepository->paginateForTenancy(
            $this->tenancyId($actor),
            $filters['search'] ?? null,
            isset($filters['status']) ? ProductStatus::from($filters['status']) : null,
            $filters['category_id'] ?? null,
            $filters['availability'] ?? null,
            isset($filters['min_price']) ? (string) $filters['min_price'] : null,
            isset($filters['max_price']) ? (string) $filters['max_price'] : null,
            (int) ($filters['per_page'] ?? 15),
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_dir'] ?? 'desc',
        );
    }

    /** @return array<int, string> */
    public function suggestions(User $actor, string $query): array
    {
        $tenancyId = $this->tenancyId($actor);

        return config('search.driver') === 'elasticsearch'
            ? $this->productSearchRepository->suggest($tenancyId, $query)
            : $this->productsRepository->suggestNamesForTenancy($tenancyId, $query);
    }

    public function find(User $actor, string $id): Product
    {
        return $this->productsRepository->findForTenancyOrFail($this->tenancyId($actor), $id);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Product
    {
        return $this->productsRepository->create([
            ...$data,
            'tenancy_id' => $this->tenancyId($actor),
            'user_id' => $actor->id,
            'status' => $data['status'] ?? ProductStatus::Active->value,
            'stock' => $data['stock'] ?? 0,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, string $id, array $data): Product
    {
        $product = $this->find($actor, $id);

        return $this->productsRepository->update($product, $data);
    }

    public function delete(User $actor, string $id): void
    {
        $this->productsRepository->delete($this->find($actor, $id));
    }

    private function tenancyId(User $actor): string
    {
        if ($actor->tenancy_id === null) {
            throw new AuthorizationException('Sua conta não está vinculada a uma empresa.');
        }

        return $actor->tenancy_id;
    }
}

<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(private ProductRepository $productsRepository) {}

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
        return $this->productsRepository->paginateForTenancy(
            $this->tenancyId($actor),
            $filters['search'] ?? null,
            isset($filters['status']) ? ProductStatus::from($filters['status']) : null,
            $filters['category_id'] ?? null,
            isset($filters['min_price']) ? (string) $filters['min_price'] : null,
            isset($filters['max_price']) ? (string) $filters['max_price'] : null,
            (int) ($filters['per_page'] ?? 15),
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_dir'] ?? 'desc',
        );
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

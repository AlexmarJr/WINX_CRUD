<?php

namespace App\Services;

use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(private CategoryRepository $categoriesRepository) {}

    /** @param array<string, mixed> $filters */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        return $this->categoriesRepository->paginateForTenancy(
            $this->tenancyId($actor),
            $filters['search'] ?? null,
            isset($filters['status']) ? CategoryStatus::from($filters['status']) : null,
            (int) ($filters['per_page'] ?? 15),
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_dir'] ?? 'desc',
        );
    }

    public function find(User $actor, string $id): Category
    {
        return $this->categoriesRepository->findForTenancyOrFail($this->tenancyId($actor), $id);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Category
    {
        return $this->categoriesRepository->create([
            ...$data,
            'tenancy_id' => $this->tenancyId($actor),
            'user_id' => $actor->id,
            'status' => $data['status'] ?? CategoryStatus::Active->value,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, string $id, array $data): Category
    {
        $category = $this->find($actor, $id);

        return $this->categoriesRepository->update($category, $data);
    }

    public function delete(User $actor, string $id): void
    {
        $category = $this->find($actor, $id);

        if ($this->categoriesRepository->hasProducts($category)) {
            throw ValidationException::withMessages([
                'category' => ['Remova os produtos vinculados antes de excluir a categoria.'],
            ]);
        }

        $this->categoriesRepository->delete($category);
    }

    private function tenancyId(User $actor): string
    {
        if ($actor->tenancy_id === null) {
            throw new AuthorizationException('Sua conta não está vinculada a uma empresa.');
        }

        return $actor->tenancy_id;
    }
}

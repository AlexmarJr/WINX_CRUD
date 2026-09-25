<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function paginateForTenancy(string $tenancyId, ?string $search, ?UserStatus $status, int $perPage, string $sortBy, string $sortDir): LengthAwarePaginator
    {
        return User::query()
            ->where('tenancy_id', $tenancyId)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%{$search}%"]);
            }))
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findForTenancyOrFail(string $tenancyId, string $id): User
    {
        return User::query()->where('tenancy_id', $tenancyId)->findOrFail($id);
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}

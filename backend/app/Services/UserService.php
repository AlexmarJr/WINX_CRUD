<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private UserRepository $usersRepository) {}

    /** @param array<string, mixed> $filters */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        return $this->usersRepository->paginateForTenancy(
            $this->tenancyId($actor),
            $filters['search'] ?? null,
            isset($filters['status']) ? UserStatus::from($filters['status']) : null,
            (int) ($filters['per_page'] ?? 20),
            $filters['sort_by'] ?? 'name',
            $filters['sort_dir'] ?? 'asc',
        );
    }

    public function find(User $actor, string $id): User
    {
        return $this->usersRepository->findForTenancyOrFail($this->tenancyId($actor), $id);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, string $id, array $data): User
    {
        $target = $this->find($actor, $id);
        Gate::forUser($actor)->authorize('update', $target);

        if ($actor->id === $target->id && (
            (isset($data['email']) && $data['email'] !== $actor->email)
            || (isset($data['role']) && $data['role'] !== $actor->role)
            || (isset($data['status']) && $data['status'] !== $actor->status->value)
        )) {
            throw ValidationException::withMessages([
                'user' => ['Altere seu e-mail no perfil. Seu próprio papel e status não podem ser alterados aqui.'],
            ]);
        }

        return $this->usersRepository->update($target, $data);
    }

    public function delete(User $actor, string $id): void
    {
        $target = $this->find($actor, $id);
        Gate::forUser($actor)->authorize('delete', $target);

        $this->usersRepository->delete($target);
    }

    /** @param array{email: string, current_password: string} $data */
    public function changeEmail(User $actor, array $data): User
    {
        $this->checkCurrentPassword($actor, $data['current_password']);

        if ($actor->email !== $data['email']) {
            $actor->email_verified_at = null;
        }

        return $this->usersRepository->update($actor, ['email' => $data['email']]);
    }

    /** @param array{password: string, current_password: string} $data */
    public function changePassword(User $actor, array $data): void
    {
        $this->checkCurrentPassword($actor, $data['current_password']);
        $actor->setRememberToken(Str::random(60));

        $this->usersRepository->update($actor, ['password' => $data['password']]);
    }

    private function checkCurrentPassword(User $actor, string $password): void
    {
        if (! Hash::check($password, $actor->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['A senha atual está incorreta.'],
            ]);
        }
    }

    private function tenancyId(User $actor): string
    {
        if ($actor->tenancy_id === null) {
            throw new AuthorizationException('Sua conta não está vinculada a uma empresa.');
        }

        return $actor->tenancy_id;
    }
}

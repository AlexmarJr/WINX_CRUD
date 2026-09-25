<?php

namespace App\Policies;

use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        return $actor->role === 'admin'
            && $actor->status === UserStatus::Active
            && $actor->tenancy_id !== null
            && $actor->tenancy_id === $target->tenancy_id;
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && $actor->id !== $target->id;
    }
}

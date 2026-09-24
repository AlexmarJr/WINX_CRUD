<?php

namespace App\Repositories;

use App\Enums\InviteStatus;
use App\Models\Invite;
use App\Models\User;

class InviteRepository
{
    public function accountExists(string $email): bool
    {
        return User::query()->whereRaw('lower(email) = ?', [$email])->exists();
    }

    public function pendingForEmail(string $email): ?Invite
    {
        return Invite::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->where('status', InviteStatus::Pending->value)
            ->first();
    }

    public function findByTokenOrFail(string $token, bool $lock = false): Invite
    {
        $query = Invite::query()->with('tenancy')->where('token', $token);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Invite
    {
        return Invite::query()->create($attributes)->load('tenancy');
    }

    public function expire(Invite $invite): void
    {
        $invite->status = InviteStatus::Expired;
        $invite->save();
    }

    public function accept(Invite $invite): void
    {
        $invite->status = InviteStatus::Accepted;
        $invite->accepted_at = now();
        $invite->save();
    }
}

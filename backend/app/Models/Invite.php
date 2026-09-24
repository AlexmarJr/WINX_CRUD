<?php

namespace App\Models;

use App\Enums\InviteStatus;
use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['email', 'tenancy_id', 'user_id', 'role', 'status', 'token', 'invite_url', 'expires_at', 'accepted_at'])]
class Invite extends Model
{
    /** @use HasFactory<InviteFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    protected function casts(): array
    {
        return [
            'status' => InviteStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}

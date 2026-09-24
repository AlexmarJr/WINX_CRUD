<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'tenancy_id', 'action', 'entity_type', 'entity_id', 'meta'])]
class Logs extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}

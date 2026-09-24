<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenancy_id', 'user_id', 'name', 'description', 'status', 'meta'])]
class Category extends Model
{
    use HasUuids, SoftDeletes;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
            'meta' => 'array',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenancy_id', 'user_id', 'category_id', 'name', 'description', 'cost', 'price', 'stock', 'status', 'meta', 'image'])]
class Product extends Model
{
    use HasUuids, SoftDeletes;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'status' => ProductStatus::class,
            'meta' => 'array',
        ];
    }
}

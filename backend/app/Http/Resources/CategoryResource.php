<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenancy_id' => $this->tenancy_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'products_count' => $this->whenCounted('products'),
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

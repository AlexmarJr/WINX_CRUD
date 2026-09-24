<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? new CategoryResource($this->category) : null),
            'name' => $this->name,
            'description' => $this->description,
            'cost' => $this->cost,
            'price' => $this->price,
            'stock' => $this->stock,
            'status' => $this->status->value,
            'meta' => $this->meta,
            'image' => $this->image,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

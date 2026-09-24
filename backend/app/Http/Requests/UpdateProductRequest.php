<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->tenancy_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where('tenancy_id', $this->user()?->tenancy_id)->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'between:0,99999999.99'],
            'price' => ['sometimes', 'required', 'numeric', 'between:0,99999999.99'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'meta' => ['sometimes', 'nullable', 'array'],
            'image' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

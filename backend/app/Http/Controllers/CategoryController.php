<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(CategoryStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort_by' => ['sometimes', Rule::in(['name', 'description', 'products_count', 'status'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        return CategoryResource::collection($this->categoryService->paginate($request->user(), $filters));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->user(), $request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $category): CategoryResource
    {
        return new CategoryResource($this->categoryService->find($request->user(), $category));
    }

    public function update(UpdateCategoryRequest $request, string $category): CategoryResource
    {
        return new CategoryResource($this->categoryService->update($request->user(), $category, $request->validated()));
    }

    public function destroy(Request $request, string $category): Response
    {
        $this->categoryService->delete($request->user(), $category);

        return response()->noContent();
    }
}

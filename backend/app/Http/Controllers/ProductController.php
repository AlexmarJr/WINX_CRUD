<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function maxPrice(Request $request): JsonResponse
    {
        return response()->json(['data' => ['max_price' => $this->productService->maxPrice($request->user())]]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'category_id' => ['sometimes', 'uuid'],
            'min_price' => ['sometimes', 'numeric', 'between:0,99999999.99'],
            'max_price' => ['sometimes', 'numeric', 'between:0,99999999.99', ...($request->has('min_price') ? ['gte:min_price'] : [])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort_by' => ['sometimes', Rule::in(['name', 'category', 'price', 'stock', 'status'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        return ProductResource::collection($this->productService->paginate($request->user(), $filters));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->user(), $request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $product): ProductResource
    {
        return new ProductResource($this->productService->find($request->user(), $product));
    }

    public function update(UpdateProductRequest $request, string $product): ProductResource
    {
        return new ProductResource($this->productService->update($request->user(), $product, $request->validated()));
    }

    public function destroy(Request $request, string $product): Response
    {
        $this->productService->delete($request->user(), $product);

        return response()->noContent();
    }
}

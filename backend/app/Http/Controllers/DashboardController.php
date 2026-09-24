<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function summary(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->productService->summary($request->user())]);
    }
}

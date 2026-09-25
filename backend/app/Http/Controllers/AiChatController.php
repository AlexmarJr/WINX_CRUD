<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiChatRequest;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiChatController extends Controller
{
    public function __construct(private AiChatService $aiChatService) {}

    public function __invoke(AiChatRequest $request): JsonResponse
    {
        try {
            $data = $this->aiChatService->reply($request->user(), $request->validated());
        } catch (HttpExceptionInterface $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getStatusCode());
        }

        return response()->json(['data' => $data, 'message' => 'OK']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort_by' => ['sometimes', Rule::in(['name', 'email', 'role', 'status'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        return UserResource::collection($this->userService->paginate($request->user(), $filters));
    }

    public function show(Request $request, string $user): UserResource
    {
        return new UserResource($this->userService->find($request->user(), $user));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->userService->update($request->user(), $user->id, $request->validated()));
    }

    public function destroy(Request $request, User $user): Response
    {
        $this->userService->delete($request->user(), $user->id);

        return response()->noContent();
    }
}

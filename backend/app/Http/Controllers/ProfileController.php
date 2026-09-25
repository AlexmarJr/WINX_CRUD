<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileEmailRequest;
use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function updateEmail(UpdateProfileEmailRequest $request): UserResource
    {
        return new UserResource($this->userService->changeEmail($request->user(), $request->validated()));
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): Response
    {
        $this->userService->changePassword($request->user(), $request->validated());

        return response()->noContent();
    }
}

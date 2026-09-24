<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInviteRequest;
use App\Http\Requests\StoreInviteRequest;
use App\Http\Resources\InviteResource;
use App\Services\InviteService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller
{
    public function __construct(private InviteService $inviteService) {}

    public function store(StoreInviteRequest $request): JsonResponse
    {
        $invite = $this->inviteService->create($request->user(), $request->validated());

        return (new InviteResource($invite))->response()->setStatusCode(201);
    }

    public function show(string $token): InviteResource
    {
        return new InviteResource($this->inviteService->findValid($token));
    }

    public function accept(AcceptInviteRequest $request, string $token): JsonResponse
    {
        $user = $this->inviteService->accept($token, $request->validated());

        event(new Registered($user));

        if ($request->hasSession()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return response()->json($user, 201);
    }
}

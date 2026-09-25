<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Tenancy;
use App\Models\User;
use App\Services\StarterInventoryService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private StarterInventoryService $starterInventoryService) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_abbreviation' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $tenancy = new Tenancy;
            $tenancy->name = $data['company_name'];
            $tenancy->abbreviation = $data['company_abbreviation'];
            $tenancy->save();

            $user = new User;
            $user->tenancy_id = $tenancy->id;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role = 'admin';
            $user->save();

            $this->starterInventoryService->createFor($tenancy, $user);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'status' => UserStatus::Active->value], $request->boolean('remember'))) {
            $inactiveUser = User::query()
                ->where('email', $credentials['email'])
                ->where('status', UserStatus::Inactive->value)
                ->first();

            if ($inactiveUser !== null && Hash::check($credentials['password'], $inactiveUser->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Sua conta foi desativada. Entre em contato com o administrador para mais informações.'],
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não conferem.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json($request->user());
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

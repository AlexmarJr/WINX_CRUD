<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
    Route::get('/invites/{token}', [InviteController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/invites/{token}/accept', [InviteController::class, 'accept'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware(EnsureActiveUser::class)->group(function (): void {
            Route::get('/user', function (Request $request) {
                return $request->user();
            });

            Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])->middleware('throttle:5,1');
            Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:5,1');
            Route::post('/ai-chat', AiChatController::class)->middleware('throttle:10,1');
            Route::post('/invites', [InviteController::class, 'store'])->middleware('throttle:10,1');
            Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('users', UserController::class)->only(['index', 'show', 'update', 'destroy']);
            Route::get('/products/max-price', [ProductController::class, 'maxPrice']);
            Route::get('/products/suggestions', [ProductController::class, 'suggestions']);
            Route::apiResource('products', ProductController::class);
        });
    });
});

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_changes_require_authentication(): void
    {
        $this->patchJson('/api/v1/profile/email', [
            'email' => 'new@example.test',
            'current_password' => 'password123',
        ])->assertUnauthorized();

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'password123',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnauthorized();
    }

    public function test_user_can_change_own_email_with_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.test',
            'password' => 'password123',
            'email_verified_at' => now(),
        ]);
        $other = User::factory()->create(['email' => 'taken@example.test']);
        $this->actingAs($user);

        $this->patchJson('/api/v1/profile/email', [
            'email' => 'new@example.test',
            'current_password' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->patchJson('/api/v1/profile/email', [
            'email' => $other->email,
            'current_password' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->patchJson('/api/v1/profile/email', [
            'email' => 'new@example.test',
            'current_password' => 'password123',
        ])->assertOk()->assertJsonPath('data.email', 'new@example.test');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.test', 'email_verified_at' => null]);
        $this->assertSame('taken@example.test', $other->fresh()->email);
    }

    public function test_user_can_change_own_password_only_with_current_password_and_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
            'remember_token' => 'old-token',
        ]);
        $this->actingAs($user);

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'wrong',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
    }

    public function test_inactive_and_deleted_users_cannot_log_in(): void
    {
        $inactive = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'password123',
            'status' => 'inactive',
        ]);
        $deleted = User::factory()->create([
            'email' => 'deleted@example.test',
            'password' => 'password123',
        ]);
        $deleted->delete();

        foreach ([$inactive->email, $deleted->email] as $email) {
            $this->postJson('/api/v1/login', [
                'email' => $email,
                'password' => 'password123',
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
        }
    }
}

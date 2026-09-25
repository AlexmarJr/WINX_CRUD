<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_is_queued_and_points_to_the_frontend(): void
    {
        Queue::fake();
        config()->set('app.frontend_url', 'http://localhost:3000');

        $user = User::factory()->create(['email' => 'ana@example.test']);

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Queue::assertPushedOn('emails', SendQueuedNotifications::class);
        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) use ($user): bool {
            $notification = $job->notification;

            $this->assertInstanceOf(PasswordResetNotification::class, $notification);
            $this->assertTrue(Password::tokenExists($user, $notification->token));
            $this->assertStringContainsString(
                '/reset-password/'.$notification->token.'?email=ana%40example.test',
                $notification->toMail($user)->actionUrl,
            );

            return true;
        });
    }

    public function test_unknown_email_has_the_same_public_response_and_sends_nothing(): void
    {
        Notification::fake();

        $known = $this->postJson('/api/v1/forgot-password', ['email' => 'missing@example.test'])
            ->assertOk()
            ->json('message');

        $another = $this->postJson('/api/v1/forgot-password', ['email' => 'another@example.test'])
            ->assertOk()
            ->json('message');

        $this->assertSame($known, $another);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_valid_token_resets_password_and_cannot_be_reused(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'ana@example.test',
            'password' => 'old-password',
        ]);

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();

        $notification = Notification::sent($user, PasswordResetNotification::class)->first();
        $this->assertNotNull($notification);

        $payload = [
            'email' => $user->email,
            'token' => $notification->token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->postJson('/api/v1/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user, $notification->token));

        $this->postJson('/api/v1/reset-password', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_expired_or_invalid_token_and_missing_confirmation_are_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'old-password']);

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
        $notification = Notification::sent($user, PasswordResetNotification::class)->first();
        $this->assertNotNull($notification);

        $payload = [
            'email' => $user->email,
            'token' => $notification->token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->postJson('/api/v1/reset-password', array_merge($payload, ['password_confirmation' => 'different']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/v1/reset-password', array_merge($payload, ['token' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->travel(61)->minutes();

        $this->postJson('/api/v1/reset-password', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}

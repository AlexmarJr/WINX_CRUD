<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Invite;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InviteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inviter_creates_a_tenancy_bound_invite_and_queues_email(): void
    {
        Mail::fake();
        $tenancy = $this->tenancy();
        $owner = User::factory()->create(['tenancy_id' => $tenancy->id]);

        $this->actingAs($owner)
            ->postJson('/api/v1/invites', ['email' => '  New@Example.test  ', 'role' => 'admin'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'new@example.test')
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.company_name', 'Empresa Teste');

        $invite = Invite::query()->firstOrFail();
        $this->assertSame($tenancy->id, $invite->tenancy_id);
        $this->assertSame($owner->id, $invite->user_id);
        $this->assertSame('pending', $invite->status->value);
        $this->assertSame(64, strlen($invite->token));
        $this->assertSame('http://localhost:3000/invite/'.$invite->token, $invite->invite_url);
        Mail::assertQueued(InvitationMail::class, function (InvitationMail $mail) use ($invite): bool {
            return $mail->queue === 'emails'
                && $mail->hasTo($invite->email)
                && str_contains($mail->render(), $invite->invite_url);
        });
    }

    public function test_existing_account_and_pending_invite_are_rejected(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['tenancy_id' => $this->tenancy()->id]);
        User::factory()->create(['email' => 'existing@example.test']);

        $this->actingAs($owner)
            ->postJson('/api/v1/invites', ['email' => 'EXISTING@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->actingAs($owner)
            ->postJson('/api/v1/invites', ['email' => 'pending@example.test'])
            ->assertCreated();

        $otherOwner = User::factory()->create(['tenancy_id' => $this->tenancy()->id]);
        $this->actingAs($otherOwner)
            ->postJson('/api/v1/invites', ['email' => 'PENDING@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('invites', 1);
        Mail::assertQueued(InvitationMail::class, 1);
    }

    public function test_soft_deleted_account_cannot_be_invited_again(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['tenancy_id' => $this->tenancy()->id]);
        $deleted = User::factory()->create(['email' => 'deleted@example.test']);
        $deleted->delete();

        $this->actingAs($owner)
            ->postJson('/api/v1/invites', ['email' => 'deleted@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('invites', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_guest_accepts_invite_and_cannot_reuse_it(): void
    {
        $tenancy = $this->tenancy();
        $invite = Invite::factory()->create([
            'tenancy_id' => $tenancy->id,
            'email' => 'invited@example.test',
            'role' => 'admin',
        ]);

        $this->getJson('/api/v1/invites/'.$invite->token)
            ->assertOk()
            ->assertJsonPath('data.email', 'invited@example.test');

        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/v1/invites/'.$invite->token.'/accept', [
                'name' => 'Pessoa Convidada',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('email', 'invited@example.test');

        $user = User::query()->where('email', 'invited@example.test')->firstOrFail();
        $this->assertSame($tenancy->id, $user->tenancy_id);
        $this->assertSame('admin', $user->role);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('invites', ['id' => $invite->id, 'status' => 'accepted']);
        $this->assertNotNull($invite->fresh()->accepted_at);

        $this->postJson('/api/v1/invites/'.$invite->token.'/accept', [
            'name' => 'Outra Pessoa',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('token')
            ->assertJsonPath('errors.token.0', 'Este convite já foi utilizado. Entre em contato com quem enviou o convite e peça um novo link.');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_expired_and_unknown_tokens_cannot_be_accepted(): void
    {
        $invite = Invite::factory()->create(['expires_at' => now()->subMinute()]);

        $expiredMessage = 'Este convite expirou. Entre em contato com quem enviou o convite e peça um novo link.';
        $missingMessage = 'Não encontramos este convite. Entre em contato com quem enviou o convite e peça um novo link.';

        $this->getJson('/api/v1/invites/'.$invite->token)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', $expiredMessage);

        $this->postJson('/api/v1/invites/'.$invite->token.'/accept', [
            'name' => 'Pessoa', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonPath('errors.token.0', $expiredMessage);

        $unknownToken = Str::random(64);

        $this->getJson('/api/v1/invites/'.$unknownToken)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', $missingMessage);

        $this->postJson('/api/v1/invites/'.$unknownToken.'/accept', [
            'name' => 'Pessoa', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonPath('errors.token.0', $missingMessage);

        $invite->delete();

        $this->getJson('/api/v1/invites/'.$invite->token)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', $missingMessage);
    }

    public function test_public_api_can_accept_an_invite_without_a_browser_session(): void
    {
        $invite = Invite::factory()->create();

        $this->postJson('/api/v1/invites/'.$invite->token.'/accept', [
            'name' => 'Pessoa via API',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('email', $invite->email);

        $this->assertDatabaseHas('users', [
            'email' => $invite->email,
            'tenancy_id' => $invite->tenancy_id,
        ]);
    }

    public function test_expired_invite_can_be_replaced_and_acceptance_requires_confirmed_password(): void
    {
        Mail::fake();
        $tenancy = $this->tenancy();
        $owner = User::factory()->create(['tenancy_id' => $tenancy->id]);
        $old = Invite::factory()->create([
            'tenancy_id' => $tenancy->id,
            'email' => 'again@example.test',
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($owner)
            ->postJson('/api/v1/invites', ['email' => 'again@example.test'])
            ->assertCreated();

        $this->assertSame('expired', $old->fresh()->status->value);
        $new = Invite::query()->where('status', 'pending')->firstOrFail();
        $this->assertSame('employee', $new->role);

        $this->postJson('/api/v1/invites/'.$new->token.'/accept', [
            'name' => 'Pessoa', 'password' => 'password123', 'password_confirmation' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertSame('pending', $new->fresh()->status->value);
    }

    private function tenancy(): Tenancy
    {
        $tenancy = new Tenancy;
        $tenancy->name = 'Empresa Teste';
        $tenancy->abbreviation = 'ET';
        $tenancy->save();

        return $tenancy;
    }
}

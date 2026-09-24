<?php

namespace Tests\Feature;

use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_current_user(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_can_register(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/register', [
                'company_name' => 'Empresa Ana',
                'company_abbreviation' => 'EA',
                'name' => 'Ana Silva',
                'email' => 'ana@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonPath('email', 'ana@example.test');

        $this->assertDatabaseHas('users', ['email' => 'ana@example.test']);
        $this->assertDatabaseHas('tenancies', ['name' => 'Empresa Ana', 'abbreviation' => 'EA']);
        $this->assertDatabaseHas('users', ['email' => 'ana@example.test', 'role' => 'admin']);
        $this->assertSame(
            Tenancy::where('name', 'Empresa Ana')->value('id'),
            User::where('email', 'ana@example.test')->value('tenancy_id'),
        );
        $this->assertAuthenticated();
    }

    public function test_registration_rejects_an_existing_email_without_creating_a_tenancy(): void
    {
        User::factory()->create(['email' => 'ana@example.test']);

        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/register', [
                'company_name' => 'Outra Empresa',
                'company_abbreviation' => 'OE',
                'name' => 'Ana Silva',
                'email' => 'ana@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('tenancies', 0);
    }

    public function test_registration_requires_company_details_and_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Ana Silva',
            'email' => 'ana@example.test',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name', 'company_abbreviation', 'password']);

        $this->assertDatabaseCount('tenancies', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@example.test',
            'password' => 'password123',
        ]);

        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/login', [
                'email' => 'ana@example.test',
                'password' => 'password123',
            ])
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertAuthenticatedAs($user);

        $this->postJson('/api/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ana@example.test',
            'password' => 'password123',
        ]);

        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/login', [
                'email' => 'ana@example.test',
                'password' => 'incorrect',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }
}

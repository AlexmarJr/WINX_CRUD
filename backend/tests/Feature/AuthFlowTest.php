<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_current_user(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_user_can_register(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/v1/register', [
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
        $tenancyId = Tenancy::where('name', 'Empresa Ana')->value('id');
        $user = User::where('email', 'ana@example.test')->firstOrFail();
        $this->assertSame($tenancyId, $user->tenancy_id);
        $this->assertSame(5, Category::query()->where('tenancy_id', $tenancyId)->count());
        $this->assertSame(50, Product::query()->where('tenancy_id', $tenancyId)->count());
        $this->assertTrue(Category::query()->where('tenancy_id', $tenancyId)->withCount('products')->get()
            ->every(fn (Category $category): bool => $category->products_count === 10));
        $this->assertDatabaseHas('products', [
            'tenancy_id' => $tenancyId,
            'user_id' => $user->id,
            'name' => 'Mouse sem fio M185',
        ]);
        $this->assertAuthenticated();
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonPath('meta.total', 5);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('meta.total', 50);
    }

    public function test_each_registration_receives_its_own_starter_inventory(): void
    {
        foreach ([
            ['Empresa A', 'EA', 'ana@example.test'],
            ['Empresa B', 'EB', 'bia@example.test'],
        ] as [$companyName, $abbreviation, $email]) {
            $this->withHeaders(['Origin' => 'http://localhost:3000'])->postJson('/api/v1/register', [
                'company_name' => $companyName,
                'company_abbreviation' => $abbreviation,
                'name' => 'Administrador',
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertCreated();

            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertSame(5, Category::query()->where('tenancy_id', $user->tenancy_id)->count());
            $this->assertSame(50, Product::query()->where('tenancy_id', $user->tenancy_id)->count());
            $this->assertSame(50, Product::query()
                ->where('tenancy_id', $user->tenancy_id)
                ->where('user_id', $user->id)
                ->whereIn('category_id', Category::query()->where('tenancy_id', $user->tenancy_id)->select('id'))
                ->count());
        }

        $this->assertDatabaseCount('categories', 10);
        $this->assertDatabaseCount('products', 100);
    }

    public function test_registration_rejects_an_existing_email_without_creating_a_tenancy(): void
    {
        User::factory()->create(['email' => 'ana@example.test']);

        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/v1/register', [
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
        $this->postJson('/api/v1/register', [
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
            ->postJson('/api/v1/login', [
                'email' => 'ana@example.test',
                'password' => 'password123',
            ])
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertAuthenticatedAs($user);

        $this->postJson('/api/v1/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ana@example.test',
            'password' => 'password123',
        ]);

        $this->withHeaders(['Origin' => 'http://localhost:3000'])
            ->postJson('/api/v1/login', [
                'email' => 'ana@example.test',
                'password' => 'incorrect',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_creates_an_admin_who_can_access_fifty_products(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'demo@winx.test')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertDatabaseHas('tenancies', [
            'id' => $user->tenancy_id,
            'name' => 'Winx Demo',
            'abbreviation' => 'DEMO',
        ]);
        $this->assertSame(5, Category::query()->where('tenancy_id', $user->tenancy_id)->count());
        $this->assertSame(50, Product::query()->where('tenancy_id', $user->tenancy_id)->where('user_id', $user->id)->count());
        $this->assertSame(50, Product::query()
            ->where('tenancy_id', $user->tenancy_id)
            ->whereIn('category_id', Category::query()->where('tenancy_id', $user->tenancy_id)->select('id'))
            ->count());

        $this->withHeaders(['Origin' => 'http://localhost:3000'])->postJson('/api/v1/login', [
            'email' => 'demo@winx.test',
            'password' => 'password123',
        ])->assertOk();
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('meta.total', 50);
    }

    public function test_repeating_the_default_seed_does_not_duplicate_the_demo_company(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, Tenancy::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame(5, Category::query()->count());
        $this->assertSame(50, Product::query()->count());
    }
}

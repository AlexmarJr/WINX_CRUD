<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/categories')->assertUnauthorized();
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
        $this->getJson('/api/v1/dashboard/summary')->assertUnauthorized();
        $this->getJson('/api/v1/products/max-price')->assertUnauthorized();
        $this->getJson('/api/products')->assertNotFound();
    }

    public function test_category_and_product_crud_is_scoped_to_the_authenticated_tenancy(): void
    {
        $user = $this->makeUser('Empresa A');
        $this->actingAs($user);

        $categoryResponse = $this->postJson('/api/v1/categories', ['name' => 'Periféricos'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenancy_id', $user->tenancy_id);
        $categoryId = $categoryResponse->json('data.id');

        $productResponse = $this->postJson('/api/v1/products', [
            'category_id' => $categoryId,
            'name' => 'Mouse sem fio',
            'cost' => 30.5,
            'price' => 59.9,
            'stock' => 5,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.category.name', 'Periféricos');
        $productId = $productResponse->json('data.id');

        $this->getJson('/api/v1/products?search=mouse&status=active&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.products_count', 1);

        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.product_count', 1)
            ->assertJsonPath('data.total_units', 5)
            ->assertJsonPath('data.low_stock.0.id', $productId);

        $this->patchJson("/api/v1/products/{$productId}", ['stock' => 8, 'status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.stock', 8)
            ->assertJsonPath('data.status', 'inactive');

        $this->deleteJson("/api/v1/categories/{$categoryId}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');

        $this->deleteJson("/api/v1/products/{$productId}")->assertNoContent();
        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->getJson("/api/v1/products/{$productId}")->assertNotFound();

        $this->deleteJson("/api/v1/categories/{$categoryId}")->assertNoContent();
        $this->assertSoftDeleted('categories', ['id' => $categoryId]);
    }

    public function test_another_tenancy_cannot_read_or_modify_inventory(): void
    {
        $owner = $this->makeUser('Empresa A');
        $other = $this->makeUser('Empresa B');
        $category = Category::query()->create([
            'tenancy_id' => $owner->tenancy_id,
            'user_id' => $owner->id,
            'name' => 'Privada',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'tenancy_id' => $owner->tenancy_id,
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Produto privado',
            'price' => 10,
            'stock' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($other);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/dashboard/summary')->assertOk()->assertJsonPath('data.product_count', 0);
        $this->getJson("/api/v1/categories/{$category->id}")->assertNotFound();
        $this->patchJson("/api/v1/products/{$product->id}", ['name' => 'Alterado'])->assertNotFound();
        $this->deleteJson("/api/v1/products/{$product->id}")->assertNotFound();
        $this->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'name' => 'Tentativa',
            'price' => 10,
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Produto privado', 'deleted_at' => null]);
    }

    public function test_product_validation_rejects_invalid_status_and_stock(): void
    {
        $user = $this->makeUser('Empresa A');
        $category = Category::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'name' => 'Periféricos',
            'status' => 'active',
        ]);

        $this->actingAs($user)->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'name' => 'Mouse',
            'price' => -1,
            'stock' => -2,
            'status' => 'unknown',
        ])->assertUnprocessable()->assertJsonValidationErrors(['price', 'stock', 'status']);
    }

    public function test_inventory_sorting_uses_all_records_before_pagination(): void
    {
        $user = $this->makeUser('Empresa A');
        $audio = Category::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'name' => 'Audio',
            'status' => 'active',
        ]);
        $video = Category::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'name' => 'Video',
            'status' => 'active',
        ]);

        foreach ([
            ['Headset', $audio->id, 9],
            ['Microfone', $audio->id, 5],
            ['Webcam', $video->id, 1],
        ] as [$name, $categoryId, $stock]) {
            Product::query()->create([
                'tenancy_id' => $user->tenancy_id,
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'name' => $name,
                'price' => 10,
                'stock' => $stock,
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);

        $this->getJson('/api/v1/products?sort_by=stock&sort_dir=asc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Webcam')
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/v1/products?sort_by=category&sort_dir=desc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Webcam');

        $this->getJson('/api/v1/categories?sort_by=products_count&sort_dir=desc&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Audio')
            ->assertJsonPath('data.0.products_count', 2);

        $this->getJson('/api/v1/products?sort_by=deleted_at')->assertUnprocessable()->assertJsonValidationErrors('sort_by');
    }

    public function test_product_price_range_is_inclusive_and_validated(): void
    {
        $user = $this->makeUser('Empresa A');
        $category = Category::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'name' => 'Periféricos',
            'status' => 'active',
        ]);

        foreach ([['Barato', '9.99'], ['Intermediário', '20.00'], ['Caro', '100.00']] as [$name, $price]) {
            Product::query()->create([
                'tenancy_id' => $user->tenancy_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $name,
                'price' => $price,
                'stock' => 1,
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/products?min_price=10&max_price=100')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
        $this->assertEqualsCanonicalizing(['Intermediário', 'Caro'], array_column($response->json('data'), 'name'));

        $this->getJson('/api/v1/products?min_price=20&max_price=20')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Intermediário');

        $this->getJson('/api/v1/products?max_price=9.99')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Barato');

        $this->getJson('/api/v1/products?min_price=100&max_price=20')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_price');
        $this->getJson('/api/v1/products?min_price=-1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('min_price');
        $this->getJson('/api/v1/products?max_price=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_price');
    }

    public function test_product_availability_filter_and_page_size(): void
    {
        $user = $this->makeUser('Empresa A');
        $category = Category::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'name' => 'Periféricos',
            'status' => 'active',
        ]);

        foreach ([['Esgotado', 0], ['Disponível A', 2], ['Disponível B', 5]] as [$name, $stock]) {
            Product::query()->create([
                'tenancy_id' => $user->tenancy_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $name,
                'price' => '20.00',
                'stock' => $stock,
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);

        $this->getJson('/api/v1/products?availability=in_stock&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/v1/products?availability=out_of_stock')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Esgotado');

        $this->getJson('/api/v1/products?availability=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('availability');
    }

    public function test_product_max_price_uses_only_non_deleted_products_from_the_authenticated_tenancy(): void
    {
        $user = $this->makeUser('Empresa A');
        $other = $this->makeUser('Empresa B');
        $category = Category::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'name' => 'Periféricos',
            'status' => 'active',
        ]);

        $createProduct = function (string $name, string $price) use ($user, $category): Product {
            return Product::query()->create([
                'tenancy_id' => $user->tenancy_id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $name,
                'price' => $price,
                'stock' => 1,
                'status' => 'active',
            ]);
        };

        $this->actingAs($user)->getJson('/api/v1/products/max-price')
            ->assertOk()
            ->assertJsonPath('data.max_price', '0.00');

        $createProduct('Mouse', '49.90');
        $deleted = $createProduct('Excluído', '999.00');
        $deleted->delete();

        $otherCategory = Category::query()->create([
            'tenancy_id' => $other->tenancy_id,
            'user_id' => $other->id,
            'name' => 'Outra categoria',
            'status' => 'active',
        ]);
        Product::query()->create([
            'tenancy_id' => $other->tenancy_id,
            'user_id' => $other->id,
            'category_id' => $otherCategory->id,
            'name' => 'Outro produto',
            'price' => '500.00',
            'stock' => 1,
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/products/max-price')
            ->assertOk()
            ->assertJsonPath('data.max_price', '49.90');
    }

    private function makeUser(string $companyName): User
    {
        $tenancy = new Tenancy;
        $tenancy->name = $companyName;
        $tenancy->save();

        return User::factory()->create(['tenancy_id' => $tenancy->id]);
    }
}

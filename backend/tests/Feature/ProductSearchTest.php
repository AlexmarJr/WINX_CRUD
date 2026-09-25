<?php

namespace Tests\Feature;

use App\Jobs\SyncCategoryProductsToSearch;
use App\Jobs\SyncProductToSearch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenancy;
use App\Models\User;
use App\Repositories\ProductSearchRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_elasticsearch_search_preserves_relevance_order_and_filters_by_tenancy(): void
    {
        [$user, $category] = $this->inventory('Empresa A');
        [$otherUser, $otherCategory] = $this->inventory('Empresa B');
        $first = $this->product($user, $category, 'Mouse com fio');
        $second = $this->product($user, $category, 'Mouse sem fio');
        $foreign = $this->product($otherUser, $otherCategory, 'Mouse privado');
        $searchBody = [];

        Http::fake(function (Request $request) use ($second, $first, $foreign, &$searchBody) {
            if ($request->method() === 'HEAD') {
                return Http::response('', 200);
            }

            $searchBody = $request->data();

            return Http::response(['hits' => [
                'total' => ['value' => 2],
                'hits' => [['_id' => $second->id], ['_id' => $foreign->id], ['_id' => $first->id]],
            ]]);
        });

        config()->set('search.driver', 'elasticsearch');
        $this->actingAs($user);
        $response = $this->getJson('/api/v1/products?search=mouse&category_id='.$category->id.'&availability=in_stock&min_price=10&max_price=100');

        $response->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id)->assertJsonPath('meta.total', 2);
        $this->assertSame($user->tenancy_id, $searchBody['query']['bool']['filter'][0]['term']['tenancy_id']);
        $this->assertSame($category->id, $searchBody['query']['bool']['filter'][1]['term']['category_id']);
        $this->assertSame(0, $searchBody['query']['bool']['filter'][2]['range']['stock']['gt']);
        $this->assertSame(10.0, $searchBody['query']['bool']['filter'][3]['range']['price']['gte']);
        $this->assertSame(100.0, $searchBody['query']['bool']['filter'][3]['range']['price']['lte']);
        $this->assertSame('name^5', $searchBody['query']['bool']['should'][0]['multi_match']['fields'][0]);

        $this->getJson('/api/v1/products?search=mouse&availability=out_of_stock')->assertOk();
        $this->assertSame(0, $searchBody['query']['bool']['filter'][1]['term']['stock']);
    }

    public function test_suggestions_are_scoped_to_the_authenticated_tenancy(): void
    {
        [$user] = $this->inventory('Empresa A');
        $body = [];
        Http::fake(function (Request $request) use (&$body) {
            if ($request->method() === 'HEAD') {
                return Http::response('', 200);
            }

            $body = $request->data();

            return Http::response(['hits' => ['hits' => [
                ['_source' => ['name' => 'Mouse sem fio']],
                ['_source' => ['name' => 'Mouse com fio']],
                ['_source' => ['name' => 'Mouse sem fio']],
            ]]]);
        });

        config()->set('search.driver', 'elasticsearch');
        $this->actingAs($user)->getJson('/api/v1/products/suggestions?q=mou')
            ->assertOk()->assertExactJson(['data' => ['Mouse sem fio', 'Mouse com fio']]);
        $this->assertSame($user->tenancy_id, $body['query']['bool']['filter'][0]['term']['tenancy_id']);
        $this->getJson('/api/v1/products/suggestions?q=x')->assertUnprocessable();
    }

    public function test_product_and_category_changes_enqueue_search_updates(): void
    {
        [$user, $category] = $this->inventory('Empresa A');
        Queue::fake([SyncProductToSearch::class, SyncCategoryProductsToSearch::class]);
        config()->set('search.driver', 'elasticsearch');
        $this->actingAs($user);

        $product = $this->product($user, $category, 'Mouse');
        Queue::assertPushed(SyncProductToSearch::class, 1);

        $product->update(['name' => 'Mouse novo']);
        $product->delete();
        Queue::assertPushed(SyncProductToSearch::class, 3);

        $category->update(['name' => 'Periféricos novos']);
        Queue::assertPushed(SyncCategoryProductsToSearch::class, 1);
    }

    public function test_sync_job_indexes_current_product_and_removes_deleted_product(): void
    {
        [$user, $category] = $this->inventory('Empresa A');
        $product = $this->product($user, $category, 'Mouse');
        $indexed = [];
        $deleted = [];
        Http::fake(function (Request $request) use (&$indexed, &$deleted) {
            if ($request->method() === 'HEAD') {
                return Http::response('', 200);
            }
            if ($request->method() === 'PUT') {
                $indexed = $request->data();

                return Http::response(['result' => 'created'], 201);
            }
            $deleted[] = $request->url();

            return Http::response(['result' => 'deleted']);
        });

        $job = new SyncProductToSearch($product->id);
        $job->handle(app(ProductSearchRepository::class));
        $this->assertSame($user->tenancy_id, $indexed['tenancy_id']);
        $this->assertSame('Mouse', $indexed['name']);
        $this->assertSame('Periféricos', $indexed['category_name']);

        $product->delete();
        $job->handle(app(ProductSearchRepository::class));
        $this->assertCount(1, $deleted);
        $this->assertStringEndsWith('/_doc/'.$product->id, $deleted[0]);
    }

    /** @return array{User, Category} */
    private function inventory(string $name): array
    {
        $tenancy = new Tenancy;
        $tenancy->name = $name;
        $tenancy->save();
        $user = User::factory()->create(['tenancy_id' => $tenancy->id]);
        $category = Category::query()->create([
            'tenancy_id' => $tenancy->id,
            'user_id' => $user->id,
            'name' => 'Periféricos',
            'status' => 'active',
        ]);

        return [$user, $category];
    }

    private function product(User $user, Category $category, string $name): Product
    {
        return Product::query()->create([
            'tenancy_id' => $user->tenancy_id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => '50.00',
            'stock' => 2,
            'status' => 'active',
        ]);
    }
}

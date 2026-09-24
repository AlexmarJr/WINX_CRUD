<?php

namespace Tests\Feature;

use App\Jobs\RecordInventoryLog;
use App\Models\Logs;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryLogObserverTest extends TestCase
{
    use DatabaseMigrations;

    public function test_create_update_and_soft_delete_log_only_changed_fields_and_the_current_actor(): void
    {
        config()->set('queue.default', 'database');

        $tenancy = new Tenancy;
        $tenancy->name = 'Empresa A';
        $tenancy->save();
        $creator = User::factory()->create(['tenancy_id' => $tenancy->id]);
        $editor = User::factory()->create(['tenancy_id' => $tenancy->id]);

        Sanctum::actingAs($creator);

        $categoryId = $this->postJson('/api/v1/categories', [
            'name' => 'Periféricos',
        ])->assertCreated()->json('data.id');

        $this->processQueuedLog(1);

        $categoryCreate = Logs::query()->where('entity_type', 'Category')->where('entity_id', $categoryId)->sole();
        $this->assertSame('create', $categoryCreate->action);
        $this->assertSame($creator->id, $categoryCreate->user_id);
        $this->assertSame($tenancy->id, $categoryCreate->tenancy_id);
        $this->assertSame([], $categoryCreate->meta['old']);
        $this->assertSame(['name' => 'Periféricos', 'status' => 'active'], $categoryCreate->meta['new']);

        $productId = $this->postJson('/api/v1/products', [
            'category_id' => $categoryId,
            'name' => 'Mouse',
            'price' => '49.90',
            'stock' => 4,
        ])->assertCreated()->json('data.id');

        $this->processQueuedLog(2);

        $productCreate = Logs::query()->where('entity_type', 'Product')->where('entity_id', $productId)->sole();
        $this->assertSame('create', $productCreate->action);
        $this->assertSame($creator->id, $productCreate->user_id);
        $this->assertSame([], $productCreate->meta['old']);
        $this->assertSame([
            'category_id' => $categoryId,
            'name' => 'Mouse',
            'price' => '49.90',
            'stock' => 4,
            'status' => 'active',
        ], $productCreate->meta['new']);

        Sanctum::actingAs($editor);

        $this->patchJson("/api/v1/products/{$productId}", [
            'name' => 'Mouse',
            'price' => '79.90',
            'stock' => 5,
        ])->assertOk();

        $this->processQueuedLog(3);

        $productUpdate = Logs::query()->where('entity_id', $productId)->where('action', 'update')->sole();
        $this->assertSame($editor->id, $productUpdate->user_id);
        $this->assertSame($tenancy->id, $productUpdate->tenancy_id);
        $this->assertSame(['price' => '49.90', 'stock' => 4], $productUpdate->meta['old']);
        $this->assertSame(['price' => '79.90', 'stock' => 5], $productUpdate->meta['new']);

        $this->patchJson("/api/v1/categories/{$categoryId}", [
            'description' => 'Acessórios',
        ])->assertOk();

        $this->processQueuedLog(4);

        $categoryUpdate = Logs::query()->where('entity_id', $categoryId)->where('action', 'update')->sole();
        $this->assertSame(['description' => null], $categoryUpdate->meta['old']);
        $this->assertSame(['description' => 'Acessórios'], $categoryUpdate->meta['new']);

        $this->deleteJson("/api/v1/products/{$productId}")->assertNoContent();
        $this->processQueuedLog(5);
        $productDelete = Logs::query()->where('entity_id', $productId)->where('action', 'delete')->sole();
        $this->assertSame($editor->id, $productDelete->user_id);
        $this->assertSame(['deleted_at' => null], $productDelete->meta['old']);
        $this->assertNotNull($productDelete->meta['new']['deleted_at']);
        $this->assertCount(1, $productDelete->meta['new']);

        $this->deleteJson("/api/v1/categories/{$categoryId}")->assertNoContent();
        $this->processQueuedLog(6);
        $categoryDelete = Logs::query()->where('entity_id', $categoryId)->where('action', 'delete')->sole();
        $this->assertSame($editor->id, $categoryDelete->user_id);
        $this->assertSame(['deleted_at' => null], $categoryDelete->meta['old']);
        $this->assertNotNull($categoryDelete->meta['new']['deleted_at']);
        $this->assertSame(6, Logs::query()->count());
    }

    public function test_log_is_recorded_only_when_queued_job_runs(): void
    {
        Queue::fake();

        $tenancy = new Tenancy;
        $tenancy->name = 'Empresa A';
        $tenancy->save();
        $actor = User::factory()->create(['tenancy_id' => $tenancy->id]);

        Sanctum::actingAs($actor);

        $categoryId = $this->postJson('/api/v1/categories', [
            'name' => 'Periféricos',
        ])->assertCreated()->json('data.id');

        $this->assertSame(0, Logs::query()->count());

        Queue::assertPushed(RecordInventoryLog::class, function (RecordInventoryLog $job) use ($actor, $tenancy, $categoryId): bool {
            $this->assertSame($actor->id, $job->userId);
            $this->assertSame($tenancy->id, $job->tenancyId);
            $this->assertSame('create', $job->action);
            $this->assertSame('Category', $job->entityType);
            $this->assertSame($categoryId, $job->entityId);
            $this->assertSame([], $job->old);
            $this->assertSame(['name' => 'Periféricos', 'status' => 'active'], $job->new);

            $job->handle();

            return true;
        });

        $this->assertSame(1, Logs::query()->count());
    }

    private function processQueuedLog(int $expectedCount): void
    {
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame($expectedCount - 1, Logs::query()->count());

        Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--tries' => 1,
        ]);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame($expectedCount, Logs::query()->count());
    }
}

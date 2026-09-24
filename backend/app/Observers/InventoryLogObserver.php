<?php

namespace App\Observers;

use App\Jobs\RecordInventoryLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class InventoryLogObserver
{
    private const EXCLUDED_ATTRIBUTES = ['id', 'user_id', 'tenancy_id', 'created_at', 'updated_at'];

    public function created(Product|Category $entity): void
    {
        $new = array_diff_key($entity->attributesToArray(), array_flip(self::EXCLUDED_ATTRIBUTES));

        $this->dispatchRecord($entity, 'create', [], $new);
    }

    public function updated(Product|Category $entity): void
    {
        $changes = array_diff_key($entity->getChanges(), array_flip(self::EXCLUDED_ATTRIBUTES));

        if ($changes === []) {
            return;
        }

        $old = array_replace(
            array_fill_keys(array_keys($changes), null),
            array_intersect_key($entity->getOriginal(), $changes),
        );
        $new = array_intersect_key($entity->attributesToArray(), $changes);

        $this->dispatchRecord($entity, 'update', $old, $new);
    }

    public function deleted(Product|Category $entity): void
    {
        $this->dispatchRecord($entity, 'delete', ['deleted_at' => null], [
            'deleted_at' => $entity->getAttribute('deleted_at'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function dispatchRecord(Product|Category $entity, string $action, array $old, array $new): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User || $actor->tenancy_id !== $entity->tenancy_id) {
            return;
        }

        RecordInventoryLog::dispatch(
            $actor->id,
            $actor->tenancy_id,
            $action,
            class_basename($entity),
            $entity->id,
            $old,
            $new,
        );
    }
}

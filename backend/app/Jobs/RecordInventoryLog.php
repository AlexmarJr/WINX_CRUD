<?php

namespace App\Jobs;

use App\Models\Logs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordInventoryLog implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function __construct(
        public string $userId,
        public string $tenancyId,
        public string $action,
        public string $entityType,
        public string $entityId,
        public array $old,
        public array $new,
    ) {}

    public function handle(): void
    {
        $this->record();
    }

    private function record(): void
    {
        Logs::query()->create([
            'user_id' => $this->userId,
            'tenancy_id' => $this->tenancyId,
            'action' => $this->action,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'meta' => ['old' => $this->old, 'new' => $this->new],
        ]);
    }
}

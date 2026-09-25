<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Tenancy;
use App\Models\User;
use App\Services\StarterInventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private const DEMO_EMAIL = 'demo@winx.test';

    public function run(StarterInventoryService $starterInventoryService): void
    {
        if (User::withTrashed()->where('email', self::DEMO_EMAIL)->exists()) {
            $this->command?->info('A conta de demonstração já existe; nenhum dado foi duplicado.');

            return;
        }

        DB::transaction(function () use ($starterInventoryService): void {
            $tenancy = new Tenancy;
            $tenancy->name = 'Winx Demo';
            $tenancy->abbreviation = 'DEMO';
            $tenancy->save();

            $user = User::factory()->create([
                'tenancy_id' => $tenancy->id,
                'name' => 'Administrador Demo',
                'email' => self::DEMO_EMAIL,
                'password' => 'password123',
                'role' => 'admin',
                'status' => UserStatus::Active->value,
            ]);

            $starterInventoryService->createFor($tenancy, $user);
        });

        $this->command?->info('Conta de demonstração criada: demo@winx.test / password123');
    }
}

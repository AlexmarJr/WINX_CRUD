<?php

namespace Tests\Feature;

use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
        $this->getJson('/api/v1/users/00000000-0000-0000-0000-000000000000')->assertUnauthorized();
    }

    public function test_users_are_listed_and_opened_only_within_the_authenticated_tenancy(): void
    {
        $tenancy = new Tenancy;
        $tenancy->name = 'Empresa A';
        $tenancy->save();

        $owner = User::factory()->create([
            'tenancy_id' => $tenancy->id,
            'name' => 'Zoe Admin',
            'role' => 'admin',
        ]);
        $employee = User::factory()->create([
            'tenancy_id' => $tenancy->id,
            'name' => 'Ana Funcionária',
            'email' => 'ana@example.test',
            'role' => 'employee',
        ]);
        $inactive = User::factory()->create([
            'tenancy_id' => $tenancy->id,
            'name' => 'Bia Inativa',
            'status' => 'inactive',
        ]);

        $otherTenancy = new Tenancy;
        $otherTenancy->name = 'Empresa B';
        $otherTenancy->save();
        $foreign = User::factory()->create(['tenancy_id' => $otherTenancy->id]);

        $this->actingAs($owner);

        $this->getJson('/api/v1/users?per_page=1&sort_by=name&sort_dir=asc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('data.0.id', $employee->id);

        $this->getJson('/api/v1/users?search=ANA@EXAMPLE.TEST')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $employee->id);

        $this->getJson('/api/v1/users?status=inactive')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $inactive->id);

        $response = $this->getJson('/api/v1/users/'.$employee->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Ana Funcionária')
            ->assertJsonPath('data.email', 'ana@example.test')
            ->assertJsonPath('data.role', 'employee')
            ->assertJsonPath('data.status', 'active');
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));

        $this->getJson('/api/v1/users/'.$foreign->id)->assertNotFound();

        DB::table('users')->where('id', $inactive->id)->update(['deleted_at' => now()]);
        $this->getJson('/api/v1/users')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/users/'.$inactive->id)->assertNotFound();
    }

    public function test_user_filters_are_validated_and_an_unlinked_account_cannot_list_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->getJson('/api/v1/users?status=unknown')->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->getJson('/api/v1/users?sort_by=password')->assertUnprocessable()->assertJsonValidationErrors('sort_by');
    }

    public function test_active_admin_can_update_and_soft_delete_another_user_in_the_same_tenancy(): void
    {
        $tenancy = $this->tenancy('Empresa A');
        $admin = User::factory()->create(['tenancy_id' => $tenancy->id, 'role' => 'admin']);
        $target = User::factory()->create(['tenancy_id' => $tenancy->id, 'role' => 'employee']);
        $foreign = User::factory()->create(['tenancy_id' => $this->tenancy('Empresa B')->id]);

        $this->actingAs($admin)
            ->patchJson('/api/v1/users/'.$target->id, [
                'name' => 'Nome atualizado',
                'email' => 'novo@example.test',
                'role' => 'admin',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome atualizado')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => 'novo@example.test',
            'role' => 'admin',
            'status' => 'inactive',
        ]);

        $this->patchJson('/api/v1/users/'.$foreign->id, ['name' => 'Outro'])
            ->assertNotFound();

        $this->deleteJson('/api/v1/users/'.$target->id)->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->getJson('/api/v1/users/'.$target->id)->assertNotFound();
        $this->getJson('/api/v1/users')->assertOk()->assertJsonPath('meta.total', 1);
        $this->deleteJson('/api/v1/users/'.$foreign->id)->assertNotFound();
    }

    public function test_employee_and_inactive_admin_cannot_modify_users(): void
    {
        $tenancy = $this->tenancy('Empresa A');
        $employee = User::factory()->create(['tenancy_id' => $tenancy->id, 'role' => 'employee']);
        $target = User::factory()->create(['tenancy_id' => $tenancy->id]);
        $inactiveAdmin = User::factory()->create([
            'tenancy_id' => $tenancy->id,
            'role' => 'admin',
            'status' => 'inactive',
        ]);

        $this->actingAs($employee)
            ->patchJson('/api/v1/users/'.$target->id, ['name' => 'Alterado'])
            ->assertForbidden();
        $this->deleteJson('/api/v1/users/'.$target->id)->assertForbidden();
        $this->patchJson('/api/v1/users/'.$target->id, ['email' => 'invalid'])
            ->assertForbidden();

        $this->actingAs($inactiveAdmin)
            ->patchJson('/api/v1/users/'.$target->id, ['name' => 'Alterado'])
            ->assertForbidden();
        $this->getJson('/api/v1/user')->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => $target->name]);
    }

    public function test_admin_cannot_remove_or_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create([
            'tenancy_id' => $this->tenancy('Empresa A')->id,
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $this->deleteJson('/api/v1/users/'.$admin->id)->assertForbidden();
        $this->patchJson('/api/v1/users/'.$admin->id, ['status' => 'inactive'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
        $this->patchJson('/api/v1/users/'.$admin->id, ['role' => 'employee'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
        $this->patchJson('/api/v1/users/'.$admin->id, ['email' => 'other@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'status' => 'active']);
    }

    public function test_user_update_validates_status_role_and_duplicate_email(): void
    {
        $tenancy = $this->tenancy('Empresa A');
        $admin = User::factory()->create(['tenancy_id' => $tenancy->id, 'role' => 'admin']);
        $target = User::factory()->create(['tenancy_id' => $tenancy->id]);
        $other = User::factory()->create(['tenancy_id' => $tenancy->id]);

        $this->actingAs($admin)
            ->patchJson('/api/v1/users/'.$target->id, [
                'email' => $other->email,
                'role' => 'owner',
                'status' => 'blocked',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'role', 'status']);
    }

    private function tenancy(string $name): Tenancy
    {
        $tenancy = new Tenancy;
        $tenancy->name = $name;
        $tenancy->save();

        return $tenancy;
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/user-statuses')->assertStatus(401);
        $this->postJson('/api/mobile/v1/user-statuses', ['status' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/user-statuses')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_the_catalog_with_counts_and_summary(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = $this->userStatus('Activo');
        $inactivo = $this->userStatus('Inactivo');
        $prueba = $this->userStatus('Prueba');

        $this->makeUser('Cliente', $activo->id);
        $this->makeUser('Cliente', $activo->id);
        $this->makeUser('Cliente', $inactivo->id);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/user-statuses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $activo->id,
                'status' => 'Activo',
                'users_count' => 2,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $inactivo->id,
                'status' => 'Inactivo',
                'users_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $prueba->id,
                'status' => 'Prueba',
                'users_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.in_use', 2);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->userStatusesPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/user-statuses')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_status(): void
    {
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/user-statuses', ['status' => '  Bloqueado  '])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_status.status', 'Bloqueado')
            ->assertJsonPath('user_status.users_count', 0)
            ->assertJsonPath('user_status.can_delete', true);

        $id = $response->json('user_status.id');

        $this->assertSame('Bloqueado', UserStatus::find($id)?->status);
    }

    public function test_duplicate_and_empty_descriptions_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        $this->userStatus('Activo');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/user-statuses', ['status' => 'Activo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/user-statuses', ['status' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_update_renames_a_status_and_ignores_its_own_unique_rule(): void
    {
        $admin = $this->makeUser('Admin');
        $status = UserStatus::create(['status' => 'En revision']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/user-statuses/'.$status->id, ['status' => 'En pausa'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_status.status', 'En pausa');

        // Guardar el mismo nombre no choca con la regla de unicidad.
        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/user-statuses/'.$status->id, ['status' => 'En pausa'])
            ->assertOk()
            ->assertJsonPath('user_status.status', 'En pausa');

        $this->assertSame('En pausa', $status->fresh()->status);
    }

    public function test_delete_removes_a_status_without_users(): void
    {
        $admin = $this->makeUser('Admin');
        $status = UserStatus::create(['status' => 'Temporal']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/user-statuses/'.$status->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Estado de usuario eliminado correctamente.');

        $this->assertNull(UserStatus::find($status->id));
    }

    public function test_a_status_used_by_users_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = $this->userStatus('Activo');

        $this->makeUser('Cliente', $activo->id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/user-statuses/'.$activo->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'USER_STATUS_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar el estado porque tiene usuarios asociados.');

        $this->assertNotNull(UserStatus::find($activo->id));
    }

    private function makeUser(string $type, ?int $statusId = null): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Usuarios',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'user_status_id' => $statusId,
            'password' => 'secret123',
        ]);
    }

    private function userStatus(string $name): UserStatus
    {
        return UserStatus::firstOrCreate(['status' => $name]);
    }

    private function userStatusesPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'estados-usuarios'],
            ['description' => 'Estados de usuarios', 'module_id' => $module->id],
        );
    }
}

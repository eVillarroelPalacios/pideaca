<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/group-statuses')->assertStatus(401);
        $this->postJson('/api/mobile/v1/group-statuses', ['description' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/group-statuses')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_the_catalog_with_counts_and_summary(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = GroupStatus::where('description', GroupStatus::STATUS_ACTIVE)->firstOrFail();

        Group::create(['description' => 'Grupo con estado', 'group_status_id' => $activo->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/group-statuses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.description', 'Activo')
            ->assertJsonPath('data.0.groups_count', 1)
            ->assertJsonPath('data.0.can_delete', false)
            ->assertJsonPath('data.1.description', 'Desactivo')
            ->assertJsonPath('data.1.groups_count', 0)
            ->assertJsonPath('data.1.can_delete', true)
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.in_use', 1);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->groupStatusesPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/group-statuses')
            ->assertOk()
            ->assertJsonPath('summary.total', 2);
    }

    public function test_store_creates_a_trimmed_status(): void
    {
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/group-statuses', ['description' => '  En revision  '])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('group_status.description', 'En revision')
            ->assertJsonPath('group_status.groups_count', 0)
            ->assertJsonPath('group_status.can_delete', true);

        $id = $response->json('group_status.id');

        $this->assertSame('En revision', GroupStatus::find($id)?->description);
    }

    public function test_duplicate_and_empty_descriptions_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/group-statuses', ['description' => 'Activo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/group-statuses', ['description' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');
    }

    public function test_update_renames_a_status_and_ignores_its_own_unique_rule(): void
    {
        $admin = $this->makeUser('Admin');
        $status = GroupStatus::create(['description' => 'En revision']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/group-statuses/'.$status->id, ['description' => 'En pausa'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('group_status.description', 'En pausa');

        // Guardar el mismo nombre no choca con la regla de unicidad.
        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/group-statuses/'.$status->id, ['description' => 'En pausa'])
            ->assertOk()
            ->assertJsonPath('group_status.description', 'En pausa');

        $this->assertSame('En pausa', $status->fresh()->description);
    }

    public function test_delete_removes_a_status_without_groups(): void
    {
        $admin = $this->makeUser('Admin');
        $status = GroupStatus::create(['description' => 'Temporal']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/group-statuses/'.$status->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Estado de grupo eliminado correctamente.');

        $this->assertNull(GroupStatus::find($status->id));
    }

    public function test_a_status_used_by_groups_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = GroupStatus::where('description', GroupStatus::STATUS_ACTIVE)->firstOrFail();

        Group::create(['description' => 'Grupo Usador', 'group_status_id' => $activo->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/group-statuses/'.$activo->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'GROUP_STATUS_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar el estado porque tiene grupos asociados.');

        $this->assertNotNull(GroupStatus::find($activo->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);
        $email = strtolower($type).'-'.uniqid().'@example.com';

        return User::create([
            'name' => $type.' Grupos',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function groupStatusesPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'estados-grupos'],
            ['description' => 'Estado de los grupos', 'module_id' => $module->id],
        );
    }
}

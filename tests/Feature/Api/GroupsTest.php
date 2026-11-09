<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Module;
use App\Models\Page;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/groups')->assertStatus(401);
        $this->postJson('/api/mobile/v1/groups', ['description' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/groups')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_groups_with_status_counts_and_filters(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = $this->groupStatus(GroupStatus::STATUS_ACTIVE);

        $used = Group::create(['description' => 'Servicios del hogar', 'group_status_id' => $activo->id]);
        SubGroup::create(['description' => 'Plomería', 'group_id' => $used->id]);
        $free = Group::create(['description' => 'Sin estado']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/groups')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $used->id,
                'description' => 'Servicios del hogar',
                'group_status_id' => $activo->id,
                'subgroups_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $free->id,
                'description' => 'Sin estado',
                'group_status_id' => null,
                'group_status' => null,
                'subgroups_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonPath('filters.group_statuses.0.description', GroupStatus::STATUS_ACTIVE)
            ->assertJsonPath('filters.default_group_status_id', $activo->id);

        $listed = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/groups')
            ->json('data'))
            ->firstWhere('id', $used->id);

        $this->assertSame(GroupStatus::STATUS_ACTIVE, $listed['group_status']['description']);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->groupsPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/groups')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_group_with_the_default_active_status(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = $this->groupStatus(GroupStatus::STATUS_ACTIVE);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/groups', [
                'description' => '  Nuevo grupo  ',
                'icon' => '  <svg></svg>  ',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('group.description', 'Nuevo grupo')
            ->assertJsonPath('group.icon', '<svg></svg>')
            ->assertJsonPath('group.group_status.description', GroupStatus::STATUS_ACTIVE)
            ->assertJsonPath('group.subgroups_count', 0)
            ->assertJsonPath('group.can_delete', true);

        $id = $response->json('group.id');

        $this->assertSame($activo->id, Group::find($id)?->group_status_id);
    }

    public function test_store_honors_an_explicit_status(): void
    {
        $admin = $this->makeUser('Admin');
        $desactivo = $this->groupStatus(GroupStatus::STATUS_INACTIVE);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/groups', [
                'description' => 'Grupo oculto',
                'group_status_id' => $desactivo->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('group.group_status.description', GroupStatus::STATUS_INACTIVE);
    }

    public function test_duplicate_empty_and_unknown_status_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        Group::create(['description' => 'Servicios del hogar']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/groups', ['description' => 'Servicios del hogar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/groups', ['description' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/groups', ['description' => 'Otro', 'group_status_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_status_id');
    }

    public function test_update_changes_the_status_and_keeps_it_when_omitted(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = $this->groupStatus(GroupStatus::STATUS_ACTIVE);
        $desactivo = $this->groupStatus(GroupStatus::STATUS_INACTIVE);

        $group = Group::create(['description' => 'Grupo A', 'group_status_id' => $activo->id]);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/groups/'.$group->id, [
                'description' => 'Grupo A',
                'group_status_id' => $desactivo->id,
            ])
            ->assertOk()
            ->assertJsonPath('group.group_status.description', GroupStatus::STATUS_INACTIVE);

        // Sin enviar el estado se conserva el actual.
        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/groups/'.$group->id, ['description' => 'Grupo A Editado'])
            ->assertOk()
            ->assertJsonPath('group.group_status.description', GroupStatus::STATUS_INACTIVE);

        $this->assertSame('Grupo A Editado', $group->fresh()->description);
    }

    public function test_delete_removes_a_group_without_subgroups(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Grupo temporal']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/groups/'.$group->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Grupo eliminado correctamente.');

        $this->assertNull(Group::find($group->id));
    }

    public function test_a_group_with_subgroups_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Grupo con sub grupos']);
        SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/groups/'.$group->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'GROUP_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar el grupo porque tiene sub grupos asociados.');

        $this->assertNotNull(Group::find($group->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Grupos',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function groupStatus(string $name): GroupStatus
    {
        return GroupStatus::firstOrCreate(['description' => $name]);
    }

    private function groupsPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'grupos'],
            ['description' => 'Grupos', 'module_id' => $module->id],
        );
    }
}

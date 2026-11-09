<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\Page;
use App\Models\Provider;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/subgroups')->assertStatus(401);
        $this->postJson('/api/mobile/v1/subgroups', ['description' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/subgroups')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_subgroups_with_groups_providers_and_summary(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Hogar']);

        $used = SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);
        Provider::create([
            'user_id' => $admin->id,
            'business_name' => 'Plomero Juan',
            'is_active' => true,
        ])->subgroups()->attach($used->id);

        $free = SubGroup::create(['description' => 'Sub grupo libre', 'group_id' => $group->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/subgroups')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $used->id,
                'description' => 'Plomería',
                'group_id' => $group->id,
                'providers_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $free->id,
                'description' => 'Sub grupo libre',
                'group_id' => $group->id,
                'providers_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonFragment(['id' => $group->id, 'description' => 'Hogar'])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.in_use', 1);

        $listed = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/subgroups')
            ->json('data'))
            ->firstWhere('id', $used->id);

        $this->assertSame('Hogar', $listed['group']['description']);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->subGroupsPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/subgroups')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_subgroup(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Hogar']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/subgroups', [
                'description' => '  Electricidad  ',
                'group_id' => $group->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('subgroup.description', 'Electricidad')
            ->assertJsonPath('subgroup.group_id', $group->id)
            ->assertJsonPath('subgroup.providers_count', 0)
            ->assertJsonPath('subgroup.can_delete', true);
    }

    public function test_duplicate_empty_and_unknown_group_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Hogar']);
        SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/subgroups', [
                'description' => 'Plomería',
                'group_id' => $group->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/subgroups', [
                'description' => '   ',
                'group_id' => $group->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/subgroups', [
                'description' => 'Otro',
                'group_id' => 99999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_id');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/subgroups', ['description' => 'Sin grupo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_id');
    }

    public function test_update_changes_the_subgroup(): void
    {
        $admin = $this->makeUser('Admin');
        $groupA = Group::create(['description' => 'Grupo A']);
        $groupB = Group::create(['description' => 'Grupo B']);
        $subgroup = SubGroup::create(['description' => 'Sub A', 'group_id' => $groupA->id]);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/subgroups/'.$subgroup->id, [
                'description' => 'Sub A Editado',
                'group_id' => $groupB->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('subgroup.description', 'Sub A Editado')
            ->assertJsonPath('subgroup.group_id', $groupB->id);

        $this->assertSame('Sub A Editado', $subgroup->fresh()->description);
    }

    public function test_delete_removes_a_subgroup_without_providers(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Hogar']);
        $subgroup = SubGroup::create(['description' => 'Sub temporal', 'group_id' => $group->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/subgroups/'.$subgroup->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sub grupo eliminado correctamente.');

        $this->assertNull(SubGroup::find($subgroup->id));
    }

    public function test_a_subgroup_with_providers_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $group = Group::create(['description' => 'Hogar']);
        $subgroup = SubGroup::create(['description' => 'Sub en uso', 'group_id' => $group->id]);
        Provider::create([
            'user_id' => $admin->id,
            'business_name' => 'Plomero Juan',
            'is_active' => true,
        ])->subgroups()->attach($subgroup->id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/subgroups/'.$subgroup->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SUBGROUP_IN_USE');

        $this->assertNotNull(SubGroup::find($subgroup->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Sub grupos',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function subGroupsPage(): Page
    {
        $module = \App\Models\Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'sub-grupos'],
            ['description' => 'Sub grupos', 'module_id' => $module->id],
        );
    }
}

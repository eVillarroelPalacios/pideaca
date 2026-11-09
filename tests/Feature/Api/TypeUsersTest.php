<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypeUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/type-users')->assertStatus(401);
        $this->postJson('/api/mobile/v1/type-users', ['description' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/type-users')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_type_users_with_user_counts_and_summary(): void
    {
        $admin = $this->makeUser('Admin');

        $used = TypeUser::create(['description' => 'Prestador']);
        User::create([
            'name' => 'Prestador Uno',
            'email' => 'prestador-'.uniqid('', true).'@example.com',
            'type_user_id' => $used->id,
            'password' => 'secret123',
        ]);

        $free = TypeUser::create(['description' => 'Tipo libre']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/type-users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $used->id,
                'description' => 'Prestador',
                'users_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $free->id,
                'description' => 'Tipo libre',
                'users_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.in_use', 2);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->typeUsersPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/type-users')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_type_user(): void
    {
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/type-users', [
                'description' => '  Nuevo tipo  ',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('type_user.description', 'Nuevo tipo')
            ->assertJsonPath('type_user.users_count', 0)
            ->assertJsonPath('type_user.can_delete', true);
    }

    public function test_duplicate_and_empty_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        TypeUser::create(['description' => 'Cliente']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/type-users', ['description' => 'Cliente'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/type-users', ['description' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');
    }

    public function test_update_changes_the_type_user(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Tipo A']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/type-users/'.$type->id, [
                'description' => 'Tipo A Editado',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type_user.description', 'Tipo A Editado');

        $this->assertSame('Tipo A Editado', $type->fresh()->description);
    }

    public function test_delete_removes_a_type_user_without_users(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Tipo temporal']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/type-users/'.$type->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tipo de usuario eliminado correctamente.');

        $this->assertNull(TypeUser::find($type->id));
    }

    public function test_a_type_user_with_users_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Tipo en uso']);
        User::create([
            'name' => 'Usuario del tipo',
            'email' => 'tipado-'.uniqid('', true).'@example.com',
            'type_user_id' => $type->id,
            'password' => 'secret123',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/type-users/'.$type->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'TYPE_USER_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar el tipo de usuario porque tiene usuarios asociados.');

        $this->assertNotNull(TypeUser::find($type->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Tipos',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function typeUsersPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'tipo-usuarios'],
            ['description' => 'Tipo de usuarios', 'module_id' => $module->id],
        );
    }
}

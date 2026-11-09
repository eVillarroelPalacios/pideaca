<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagedModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_without_param_returns_the_own_menu(): void
    {
        $admin = $this->makeUser('Admin');
        $page = $this->makePage('usuarios');
        $admin->pages()->sync([$page->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/menu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('managed_user', null)
            ->assertJsonFragment(['id' => $page->id, 'url' => 'usuarios']);
    }

    public function test_admin_sees_the_managed_users_menu(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');
        $page = $this->makePage('usuarios');
        $other = $this->makePage('grupos');
        $prestador->pages()->sync([$page->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/menu?managed_user_id='.$prestador->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('managed_user.id', $prestador->id)
            ->assertJsonPath('managed_user.email', $prestador->email);

        $ids = collect($response->json('data.modules'))
            ->flatMap(fn ($module) => array_column($module['pages'], 'id'))
            ->all();

        $this->assertContains($page->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_menu_managed_rejects_non_admin_and_unknown_users(): void
    {
        $client = $this->makeUser('Cliente');
        $prestador = $this->makeUser('Prestador');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/menu?managed_user_id='.$prestador->id)
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $admin = User::where('email', 'like', 'Admin%')->first()
            ?? $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/menu?managed_user_id=99999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_admin_users_scoped_to_the_managed_account(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');
        $secondary = $this->makeSecondary($prestador, 'Ayudante');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users?managed_user_id='.$prestador->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('account_email', $prestador->email)
            ->assertJsonPath('managed_user.id', $prestador->id)
            ->assertJsonFragment(['id' => $prestador->id, 'is_principal' => true])
            ->assertJsonFragment(['id' => $secondary->id, 'is_principal' => false])
            ->assertJsonPath('limits.secondary_count', 1);
    }

    public function test_admin_users_managed_rejects_non_admin_and_unknown_users(): void
    {
        $client = $this->makeUser('Cliente');
        $prestador = $this->makeUser('Prestador');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users?managed_user_id='.$prestador->id)
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $admin = $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users?managed_user_id=99999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_admin_creates_secondary_in_the_managed_account(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users?managed_user_id='.$prestador->id, [
                'name' => 'Ayudante gestionado',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user.email', $prestador->email)
            ->assertJsonPath('user.is_principal', false);
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Gestionada',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function makeSecondary(User $principal, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => $principal->email,
            'type_user_id' => $principal->type_user_id,
            'password' => 'secret123',
        ]);
    }

    private function makePage(string $url): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => $url],
            ['description' => ucfirst($url), 'module_id' => $module->id],
        );
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Country;
use App\Models\Module;
use App\Models\Page;
use App\Models\Region;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/admin-users')->assertStatus(401);
        $this->postJson('/api/mobile/v1/admin-users', ['name' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_account_users_with_catalog_filters_and_limits(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('account_email', $admin->email)
            ->assertJsonFragment([
                'id' => $admin->id,
                'is_principal' => true,
                'is_self' => true,
                'can_edit' => true,
            ])
            ->assertJsonFragment([
                'id' => $secondary->id,
                'is_principal' => false,
                'is_self' => false,
            ])
            ->assertJsonPath('limits.max_secondary', 3)
            ->assertJsonPath('limits.secondary_count', 1)
            ->assertJsonPath('limits.can_add', true);
    }

    public function test_store_creates_a_secondary_inheriting_role_and_pages(): void
    {
        $admin = $this->makeUser('Admin');
        $activo = UserStatus::firstOrCreate(['status' => 'Activo']);
        $admin->update(['user_status_id' => $activo->id]);
        $page = $this->usersPage();
        $admin->pages()->sync([$page->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users', [
                'name' => '  Nuevo ayudante  ',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.name', 'Nuevo ayudante')
            ->assertJsonPath('user.email', $admin->email)
            ->assertJsonPath('user.is_principal', false);

        $created = User::where('name', 'Nuevo ayudante')->first();

        $this->assertNotNull($created);
        $this->assertSame($admin->type_user_id, $created->type_user_id);
        $this->assertTrue($created->pages()->where('pages.id', $page->id)->exists());
    }

    public function test_store_respects_the_three_secondaries_limit(): void
    {
        $admin = $this->makeUser('Admin');

        for ($i = 1; $i <= 3; $i++) {
            $this->makeSecondary($admin, 'Ayudante '.$i);
        }

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users', [
                'name' => 'Uno de más',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ACCOUNT_USER_LIMIT');
    }

    public function test_update_changes_name_status_and_role(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');
        $activo = UserStatus::firstOrCreate(['status' => 'Activo']);
        $inactivo = UserStatus::firstOrCreate(['status' => 'Inactivo']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$secondary->id, [
                'name' => 'Ayudante Editado',
                'email' => $secondary->email,
                'user_status_id' => $inactivo->id,
                'type_user_id' => $secondary->type_user_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.name', 'Ayudante Editado')
            ->assertJsonPath('user.status', 'Inactivo');

        $this->assertSame('Ayudante Editado', $secondary->fresh()->name);
        $this->assertSame($inactivo->id, $secondary->fresh()->user_status_id);
    }

    public function test_update_can_change_the_password(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');
        $oldHash = $secondary->password;

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$secondary->id, [
                'name' => $secondary->name,
                'email' => $secondary->email,
                'password' => 'nueva-clave-123',
                'password_confirmation' => 'nueva-clave-123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotSame($oldHash, $secondary->fresh()->password);
    }

    public function test_update_rejects_a_short_or_mismatched_password(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$secondary->id, [
                'name' => $secondary->name,
                'email' => $secondary->email,
                'password' => 'corta',
                'password_confirmation' => 'corta',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$secondary->id, [
                'name' => $secondary->name,
                'email' => $secondary->email,
                'password' => 'nueva-clave-123',
                'password_confirmation' => 'otra-clave-456',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_admin_lists_everyone_and_manages_outsiders(): void
    {
        $admin = $this->makeUser('Admin');
        $outsider = $this->makeUser('Cliente');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users')
            ->assertOk()
            ->assertJsonFragment(['id' => $outsider->id])
            ->assertJsonPath('filters.types.0.description', 'Admin');

        // El Admin edita e inactiva usuarios fuera de su cuenta.
        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$outsider->id, [
                'name' => 'Cliente Editado',
                'email' => $outsider->email,
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Cliente Editado');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$outsider->id)
            ->assertOk()
            ->assertJsonPath('message', 'Usuario inactivado correctamente.');
    }

    public function test_a_secondary_cannot_touch_the_principal(): void
    {
        $principal = $this->makeUser('Prestador');
        $principal->pages()->sync([$this->usersPage()->id]);
        $secondary = $this->makeSecondary($principal, 'Ayudante');
        $secondary->pages()->sync([$this->usersPage()->id]);

        $this->actingAs($secondary, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$principal->id, [
                'name' => 'Principal tocado',
                'email' => $principal->email,
            ])
            ->assertStatus(403);
    }

    public function test_destroy_inactivates_and_detaches_pages(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');
        $secondary->pages()->sync([$this->usersPage()->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$secondary->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario inactivado correctamente.');

        $this->assertSame('Inactivo', $secondary->fresh()->status->status);
        $this->assertSame(0, $secondary->fresh()->pages()->count());
    }

    public function test_cannot_inactivate_your_own_account(): void
    {
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$admin->id)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_attach_and_detach_pages(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');
        $page = $this->usersPage();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$secondary->id.'/pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cascaded', false);

        $this->assertTrue($secondary->fresh()->pages()->where('pages.id', $page->id)->exists());

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$secondary->id.'/pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse($secondary->fresh()->pages()->where('pages.id', $page->id)->exists());
    }

    public function test_store_and_update_address(): void
    {
        $admin = $this->makeUser('Admin');
        $secondary = $this->makeSecondary($admin, 'Ayudante');
        $country = Country::create(['iso_code' => 'AR', 'name' => 'Argentina']);
        $region = Region::create(['country_id' => $country->id, 'name' => 'Buenos Aires', 'type' => 'province']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$secondary->id.'/addresses', [
                'country_id' => $country->id,
                'province_id' => $region->id,
                'street' => 'Av. San Martín',
                'number' => '1234',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.primary_address.street', 'Av. San Martín');

        $addressId = $admin->fresh()->primaryAddress->id;

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$secondary->id.'/addresses/'.$addressId, [
                'country_id' => $country->id,
                'province_id' => $region->id,
                'street' => 'Av. Belgrano',
                'number' => '999',
            ])
            ->assertOk()
            ->assertJsonPath('user.primary_address.street', 'Av. Belgrano');
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Gestión',
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

    private function usersPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'usuarios'],
            ['description' => 'Usuarios', 'module_id' => $module->id],
        );
    }
}

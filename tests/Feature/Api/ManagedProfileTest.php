<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\ProviderService;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagedProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/admin-users/1/profile')->assertStatus(401);
    }

    public function test_rejects_non_admin_users(): void
    {
        $prestador = $this->makeUser('Prestador');
        $prestador->pages()->sync([$this->usersPage()->id]);
        $target = $this->makeUser('Prestador');

        $this->actingAs($prestador, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users/'.$target->id.'/profile')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($prestador, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$target->id.'/provider', [
                'business_name' => 'X',
            ])
            ->assertStatus(403);
    }

    public function test_admin_gets_the_managed_profile_shape(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');
        Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Pizzería',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users/'.$prestador->id.'/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.name', $prestador->name)
            ->assertJsonPath('user.role', 'Prestador')
            ->assertJsonPath('flags.is_prestador', true)
            ->assertJsonPath('flags.can_configure_business', true)
            ->assertJsonPath('provider.business_name', 'Pizzería')
            ->assertJsonPath('address_shared', false)
            ->assertJsonPath('default_vehicle_type', 'moto_150');
    }

    public function test_admin_updates_the_managed_provider(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$prestador->id.'/provider', [
                'business_name' => '  Pizzería Los Hermanos  ',
                'phone' => '123',
                'hours' => ['mon' => ['open' => true, 'shifts' => []]],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provider.business_name', 'Pizzería Los Hermanos')
            ->assertJsonPath('provider.phone', '123');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$prestador->id.'/provider', [
                'business_name' => '   ',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('business_name');
    }

    public function test_admin_manages_the_managed_services(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');

        // Sin negocio todavia.
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users/'.$prestador->id.'/services')
            ->assertOk()
            ->assertJsonPath('services', []);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$prestador->id.'/services', [
                'name' => 'Pizza',
            ])
            ->assertStatus(400);

        Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Pizzería',
            'is_active' => true,
        ]);

        $id = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$prestador->id.'/services', [
                'name' => '  Pizza a la piedra  ',
                'description' => 'Rica',
            ])
            ->assertStatus(201)
            ->assertJsonPath('service.name', 'Pizza a la piedra')
            ->json('service.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/admin-users/'.$prestador->id.'/services/'.$id, [
                'name' => 'Pizza editada',
            ])
            ->assertOk()
            ->assertJsonPath('service.name', 'Pizza editada');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$prestador->id.'/services/'.$id)
            ->assertOk()
            ->assertJsonPath('message', 'Servicio eliminado correctamente.');

        $this->assertNull(ProviderService::find($id));

        // Un servicio de otro negocio no se puede tocar.
        $other = $this->makeUser('Prestador');
        $otherProvider = Provider::create([
            'user_id' => $other->id,
            'business_name' => 'Otro',
            'is_active' => true,
        ]);
        $foreign = ProviderService::create([
            'provider_id' => $otherProvider->id,
            'name' => 'Ajeno',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$prestador->id.'/services/'.$foreign->id)
            ->assertStatus(404);
    }

    public function test_admin_manages_the_managed_subgroups_and_service_types(): void
    {
        $admin = $this->makeUser('Admin');
        $prestador = $this->makeUser('Prestador');
        $group = Group::create(['description' => 'Hogar']);
        $subgroup = SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);
        $type = ServiceType::create([
            'code' => 'URG',
            'name' => 'Urgencia',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Plomero',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users/'.$prestador->id.'/subgroups')
            ->assertOk()
            ->assertJsonFragment(['id' => $subgroup->id, 'description' => 'Plomería'])
            ->assertJsonPath('selected', []);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$prestador->id.'/subgroups/toggle', [
                'subgroup_id' => $subgroup->id,
            ])
            ->assertOk()
            ->assertJsonPath('selected', [$subgroup->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/admin-users/'.$prestador->id.'/service-types')
            ->assertOk()
            ->assertJsonFragment(['id' => $type->id, 'code' => 'URG']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$prestador->id.'/service-types/toggle', [
                'service_type_id' => $type->id,
            ])
            ->assertOk()
            ->assertJsonPath('selected', [$type->id]);
    }

    public function test_images_validate_and_reject_foreign_ones(): void
    {
        $admin = $this->makeUser('Admin');
        $cliente = $this->makeUser('Cliente');

        // No es prestador anunciante: 403 igual que en la web.
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$cliente->id.'/images', [])
            ->assertStatus(403);

        $prestador = $this->makeUser('Prestador');
        $activo = \App\Models\UserStatus::firstOrCreate(['status' => 'Activo']);
        $prestador->update(['user_status_id' => $activo->id]);
        $provider = Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Plomero',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/admin-users/'.$prestador->id.'/images', [])
            ->assertStatus(422);

        $other = $this->makeUser('Prestador');
        $otherProvider = Provider::create([
            'user_id' => $other->id,
            'business_name' => 'Otro',
            'is_active' => true,
        ]);
        $foreign = ProviderImage::create([
            'provider_id' => $otherProvider->id,
            'image_path' => 'x.jpg',
            'image_type' => 'publicidad',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/admin-users/'.$prestador->id.'/images/'.$foreign->id)
            ->assertStatus(404);
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Gestionado',
            'email' => $email,
            'type_user_id' => $typeUser->id,
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

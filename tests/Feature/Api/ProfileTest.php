<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\Country;
use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ProviderDriver;
use App\Models\Region;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/profile')->assertStatus(401);
        $this->getJson('/api/mobile/v1/profile/users')->assertStatus(401);
    }

    public function test_client_profile_flags_and_initial_address_panel(): void
    {
        $client = $this->makeUser('Cliente');

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.role', 'Cliente')
            ->assertJsonPath('flags.is_principal', true)
            ->assertJsonPath('flags.can_configure_business', false)
            ->assertJsonPath('flags.can_see_address', true)
            ->assertJsonPath('flags.can_manage_users', false)
            // Sin direccion, el titular arranca en Mi Direccion.
            ->assertJsonPath('flags.initial_panel', 'direccion')
            ->assertJsonPath('provider', null)
            ->assertJsonPath('address', null)
            ->assertJsonPath('address_shared', false);

        $this->assertNotEmpty($response->json('vehicle_types'));
        $this->assertSame('moto_150', $response->json('default_vehicle_type'));
    }

    public function test_prestador_with_address_lands_on_business_and_exposes_provider(): void
    {
        $prestador = $this->makeUser('Prestador');
        $this->makeAddress($prestador);
        $provider = Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Pizzería Los Hermanos',
            'is_active' => true,
            'hours' => ['mon' => ['open' => true, 'shifts' => [['from' => '09:00', 'to' => '18:00']]]],
        ]);

        $this->actingAs($prestador, 'sanctum')
            ->getJson('/api/mobile/v1/profile')
            ->assertOk()
            ->assertJsonPath('flags.can_configure_business', true)
            ->assertJsonPath('flags.can_see_trusted_drivers', true)
            ->assertJsonPath('flags.initial_panel', 'negocio')
            ->assertJsonPath('provider.id', $provider->id)
            ->assertJsonPath('provider.business_name', 'Pizzería Los Hermanos')
            ->assertJsonPath('provider.hours.mon.open', true);
    }

    public function test_secondary_inherits_address_and_cannot_configure_business(): void
    {
        $principal = $this->makeUser('Prestador');
        $secondary = $this->makeUser('Prestador', $principal->email);
        $this->makeAddress($principal);

        $this->actingAs($secondary, 'sanctum')
            ->getJson('/api/mobile/v1/profile')
            ->assertOk()
            ->assertJsonPath('flags.is_principal', false)
            ->assertJsonPath('flags.can_configure_business', false)
            ->assertJsonPath('address_shared', true)
            ->assertJsonPath('address.street', 'Av. San Martín')
            ->assertJsonPath('flags.initial_panel', 'cuenta');

        $this->actingAs($secondary, 'sanctum')
            ->putJson('/api/mobile/v1/profile/address', ['country_id' => Country::first()->id])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_update_profile_and_password_rules(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->putJson('/api/mobile/v1/profile', ['name' => 'Nombre Nuevo'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nombre Nuevo');

        $this->actingAs($client, 'sanctum')
            ->putJson('/api/mobile/v1/profile/password', [
                'current_password' => 'incorrecta',
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($client, 'sanctum')
            ->putJson('/api/mobile/v1/profile/password', [
                'current_password' => 'secret123',
                'password' => 'nueva12345',
                'password_confirmation' => 'nueva12345',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_driver_can_update_vehicle_and_plate_is_uppercased(): void
    {
        $driver = $this->makeUser('Repartidor');

        $this->actingAs($driver, 'sanctum')
            ->patchJson('/api/mobile/v1/profile/vehicle', [
                'vehicle_type' => 'auto',
                'vehicle_plate' => 'ab123cd',
            ])
            ->assertOk()
            ->assertJsonPath('vehicle_type', 'auto')
            ->assertJsonPath('vehicle_plate', 'AB123CD');

        $client = $this->makeUser('Cliente', 'otro@example.com');

        $this->actingAs($client, 'sanctum')
            ->patchJson('/api/mobile/v1/profile/vehicle', ['vehicle_type' => 'auto'])
            ->assertStatus(403);
    }

    public function test_update_provider_creates_business_with_hours(): void
    {
        $prestador = $this->makeUser('Prestador');

        $this->actingAs($prestador, 'sanctum')
            ->putJson('/api/mobile/v1/profile/provider', [
                'business_name' => 'Mi Negocio',
                'description' => 'Descripción',
                'phone' => '123',
                'whatsapp' => '456',
                'zone' => 'Centro',
                'promo' => '2x1',
                'hours' => [
                    'mon' => ['open' => true, 'shifts' => [['from' => '09:00', 'to' => '18:00']]],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('provider.business_name', 'Mi Negocio')
            ->assertJsonPath('provider.hours.mon.shifts.0.from', '09:00');

        $this->assertDatabaseHas('providers', [
            'user_id' => $prestador->id,
            'business_name' => 'Mi Negocio',
        ]);
    }

    public function test_services_crud_uses_the_provider_of_the_account(): void
    {
        $principal = $this->makeUser('Prestador');
        $secondary = $this->makeUser('Prestador', $principal->email);
        Provider::create([
            'user_id' => $principal->id,
            'business_name' => 'Negocio',
            'is_active' => true,
        ]);

        $created = $this->actingAs($secondary, 'sanctum')
            ->postJson('/api/mobile/v1/profile/services', [
                'name' => 'Pizza',
                'description' => 'A la piedra',
            ])
            ->assertStatus(201)
            ->assertJsonPath('service.name', 'Pizza');

        $serviceId = $created->json('service.id');

        $this->actingAs($secondary, 'sanctum')
            ->getJson('/api/mobile/v1/profile/services')
            ->assertOk()
            ->assertJsonPath('services.0.name', 'Pizza');

        $this->actingAs($secondary, 'sanctum')
            ->putJson('/api/mobile/v1/profile/services/'.$serviceId, ['name' => 'Pizza especial'])
            ->assertOk()
            ->assertJsonPath('service.name', 'Pizza especial');

        $this->actingAs($secondary, 'sanctum')
            ->deleteJson('/api/mobile/v1/profile/services/'.$serviceId)
            ->assertOk();

        $this->assertDatabaseCount('provider_services', 0);
    }

    public function test_subgroups_toggle_creates_provider_and_service_types_require_page(): void
    {
        $prestador = $this->makeUser('Prestador');
        $group = Group::create(['description' => 'Servicios del Hogar & Bienestar']);
        $subgroup = SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);

        $this->actingAs($prestador, 'sanctum')
            ->getJson('/api/mobile/v1/profile/subgroups')
            ->assertOk()
            ->assertJsonPath('selected', []);

        $this->actingAs($prestador, 'sanctum')
            ->postJson('/api/mobile/v1/profile/subgroups/toggle', ['subgroup_id' => $subgroup->id])
            ->assertOk()
            ->assertJsonPath('selected.0', $subgroup->id);

        $this->assertDatabaseHas('providers', ['user_id' => $prestador->id]);

        // Los tipos de servicio exigen la pagina ordenes-servicio, igual que la web.
        $this->actingAs($prestador, 'sanctum')
            ->getJson('/api/mobile/v1/profile/service-types')
            ->assertStatus(403);

        $prestador->pages()->sync([$this->page('ordenes-servicio', 'Solicitudes')->id]);
        ServiceType::create(['code' => 'EMERGENCY', 'name' => 'Urgencia']);

        $this->actingAs($prestador, 'sanctum')
            ->getJson('/api/mobile/v1/profile/service-types')
            ->assertOk()
            ->assertJsonPath('service_types.0.code', 'EMERGENCY');
    }

    public function test_trusted_drivers_require_one_active_driver_to_enable_mode(): void
    {
        $prestador = $this->makeUser('Prestador');
        $driver = $this->makeUser('Repartidor', 'driver@example.com');
        $provider = Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Negocio',
            'is_active' => true,
        ]);

        $this->actingAs($prestador, 'sanctum')
            ->putJson('/api/mobile/v1/profile/trusted-drivers/settings', ['enabled' => true])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($prestador, 'sanctum')
            ->postJson('/api/mobile/v1/profile/trusted-drivers', ['user_id' => $driver->id])
            ->assertOk()
            ->assertJsonPath('drivers.0.user_id', $driver->id);

        $this->actingAs($prestador, 'sanctum')
            ->putJson('/api/mobile/v1/profile/trusted-drivers/settings', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('provider.trusted_drivers_enabled', true);

        $link = ProviderDriver::first();

        $this->actingAs($prestador, 'sanctum')
            ->deleteJson('/api/mobile/v1/profile/trusted-drivers/'.$link->id)
            ->assertStatus(422)
            ->assertJsonPath('code', 'last_active_driver');

        $this->actingAs($prestador, 'sanctum')
            ->deleteJson('/api/mobile/v1/profile/trusted-drivers/'.$link->id.'?disable_mode=1')
            ->assertOk();

        $this->assertFalse($provider->fresh()->trusted_drivers_enabled);
    }

    public function test_account_users_list_create_inherit_and_limit(): void
    {
        $principal = $this->makeUser('Prestador', 'cuenta@example.com');
        $page = $this->page('solicitar-servicio', 'Solicitudes');
        $principal->pages()->sync([$page->id]);

        $this->actingAs($principal, 'sanctum')
            ->getJson('/api/mobile/v1/profile/users')
            ->assertOk()
            ->assertJsonPath('data.0.is_principal', true)
            ->assertJsonPath('limits.max_secondary', 3)
            ->assertJsonPath('catalog.modules.0.description', 'Solicitudes');

        $created = $this->actingAs($principal, 'sanctum')
            ->postJson('/api/mobile/v1/profile/users', [
                'name' => 'Secundario Uno',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertStatus(201)
            ->assertJsonPath('user.email', 'cuenta@example.com')
            ->assertJsonPath('user.role', 'Prestador')
            ->assertJsonPath('user.page_ids.0', $page->id);

        $secondary = User::find($created->json('user.id'));
        $this->assertSame($principal->type_user_id, $secondary->type_user_id);

        foreach (['Dos', 'Tres'] as $name) {
            $this->actingAs($principal, 'sanctum')
                ->postJson('/api/mobile/v1/profile/users', [
                    'name' => 'Secundario '.$name,
                    'password' => 'secret123',
                    'password_confirmation' => 'secret123',
                ])
                ->assertStatus(201);
        }

        $this->actingAs($principal, 'sanctum')
            ->postJson('/api/mobile/v1/profile/users', [
                'name' => 'Cuarto',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ACCOUNT_USER_LIMIT');
    }

    public function test_secondary_cannot_modify_principal_and_cannot_inactivate_self(): void
    {
        $principal = $this->makeUser('Prestador', 'cuenta@example.com');
        $secondary = $this->makeUser('Prestador', 'cuenta@example.com');

        // El secundario no tiene la pagina usuarios ni es principal: sin permiso.
        $this->actingAs($secondary, 'sanctum')
            ->getJson('/api/mobile/v1/profile/users')
            ->assertStatus(403);

        // Como Admin, el principal si puede administrar y modificarse.
        $this->actingAs($principal, 'sanctum')
            ->putJson('/api/mobile/v1/profile/users/'.$secondary->id, ['name' => 'Renombrado'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Renombrado');

        $this->actingAs($principal, 'sanctum')
            ->deleteJson('/api/mobile/v1/profile/users/'.$principal->id)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_detaching_a_page_from_principal_cascades_to_account(): void
    {
        $admin = $this->makeUser('Admin', 'cuenta@example.com');
        $secondary = $this->makeUser('Prestador', 'cuenta@example.com');
        $page = $this->page('reportes', 'Reportes');
        $admin->pages()->sync([$page->id]);
        $secondary->pages()->sync([$page->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/profile/users/'.$admin->id.'/pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('cascaded', true);

        $this->assertFalse($admin->fresh()->pages()->where('pages.id', $page->id)->exists());
        $this->assertFalse($secondary->fresh()->pages()->where('pages.id', $page->id)->exists());
    }

    public function test_countries_and_regions(): void
    {
        $client = $this->makeUser('Cliente');
        $country = Country::firstOrCreate(
            ['iso_code' => 'AR'],
            ['name' => 'Argentina', 'is_active' => true]
        );
        Region::create(['country_id' => $country->id, 'name' => 'Centro', 'type' => 'province']);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/profile/countries')
            ->assertOk()
            ->assertJsonPath('data.0.name', $country->name);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/profile/regions?country_id='.$country->id)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Centro');
    }

    public function test_invalid_image_is_rejected_before_writing(): void
    {
        $prestador = $this->makeUser('Prestador');
        Provider::create([
            'user_id' => $prestador->id,
            'business_name' => 'Negocio',
            'is_active' => true,
        ]);

        $this->actingAs($prestador, 'sanctum')
            ->postJson('/api/mobile/v1/profile/images', [
                'image' => UploadedFile::fake()->create('nota.txt', 1, 'text/plain'),
            ])
            ->assertStatus(422);
    }

    private function makeUser(string $type, ?string $email = null): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => $type.' '.uniqid(),
            'email' => $email ?? uniqid('user').'@example.com',
            'type_user_id' => $typeUser->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function makeAddress(User $user): Address
    {
        $country = Country::firstOrCreate(
            ['iso_code' => 'AR'],
            ['name' => 'Argentina', 'is_active' => true]
        );

        return $user->addresses()->create([
            'country_id' => $country->id,
            'street' => 'Av. San Martín',
            'number' => '1234',
            'is_primary' => true,
        ]);
    }

    private function page(string $url, string $moduleDescription): Page
    {
        $module = Module::firstOrCreate(['description' => $moduleDescription]);

        return Page::create([
            'description' => ucfirst(str_replace('-', ' ', $url)),
            'url' => $url,
            'module_id' => $module->id,
        ]);
    }
}

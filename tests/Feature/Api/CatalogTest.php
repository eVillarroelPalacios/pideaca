<?php

namespace Tests\Feature\Api;

use App\Models\Favorite;
use App\Models\Group;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\ProviderService;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_providers_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/providers')->assertStatus(401);
    }

    public function test_lists_only_active_providers_of_the_service_group(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $visible = $this->makeProvider('Plomero Juan', isActive: true, inServiceGroup: true);
        $this->makeProvider('Plomero Pausado', isActive: false, inServiceGroup: true);
        $this->makeProvider('Panadería Rica', isActive: true, inServiceGroup: false);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 15);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($visible->id, $data[0]['id']);
        $this->assertSame('Plomero Juan', $data[0]['business_name']);
        $this->assertFalse($data[0]['is_favorite']);
        $this->assertArrayHasKey('is_available_now', $data[0]);
        $this->assertArrayHasKey('availability_label', $data[0]);
        $this->assertArrayHasKey('service_types', $data[0]);
    }

    public function test_filters_by_search(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $this->makeProvider('Plomero Juan', isActive: true, inServiceGroup: true);
        $this->makeProvider('Electricista Ana', isActive: true, inServiceGroup: true);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers?search=plome')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.business_name', 'Plomero Juan');
    }

    public function test_filters_by_service_type(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $emergency = ServiceType::create(['code' => 'EMERGENCY', 'name' => 'Urgencia']);

        $urgent = $this->makeProvider('Plomero 24h', isActive: true, inServiceGroup: true);
        $urgent->serviceTypes()->attach($emergency->id);
        $this->makeProvider('Jardinero Pepe', isActive: true, inServiceGroup: true);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers?type=EMERGENCY')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.business_name', 'Plomero 24h');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers?type=SCHEDULED')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_filters_favorites(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $favorite = $this->makeProvider('Plomero Juan', isActive: true, inServiceGroup: true);
        $this->makeProvider('Electricista Ana', isActive: true, inServiceGroup: true);
        Favorite::create(['user_id' => $client->id, 'provider_id' => $favorite->id]);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers?favorite=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.business_name', 'Plomero Juan')
            ->assertJsonPath('data.0.is_favorite', true);
    }

    public function test_rejects_invalid_search_params(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers?per_page=200')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_show_returns_the_provider_detail(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $provider = $this->makeProvider('Plomero Juan', isActive: true, inServiceGroup: true);
        ProviderService::create([
            'provider_id' => $provider->id,
            'name' => 'Destape de cañerías',
            'description' => 'Servicio a domicilio.',
        ]);
        ProviderImage::create([
            'provider_id' => $provider->id,
            'image_path' => 'local.jpg',
            'image_type' => 'local',
            'is_primary' => true,
        ]);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers/'.$provider->id)
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.business_name', 'Plomero Juan')
            ->assertJsonPath('data.services.0.name', 'Destape de cañerías')
            ->assertJsonPath('data.images.0.image_type', 'local')
            ->assertJsonPath('data.eta_minutes', 30);
    }

    public function test_show_returns_404_for_hidden_providers(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $paused = $this->makeProvider('Plomero Pausado', isActive: false, inServiceGroup: true);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers/'.$paused->id)
            ->assertStatus(404)
            ->assertJson(['success' => false, 'code' => 'PROVIDER_NOT_FOUND']);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/providers/999999')
            ->assertStatus(404);
    }

    private function makeClient(): User
    {
        $type = TypeUser::create(['description' => 'Cliente']);
        $status = UserStatus::create(['status' => 'Activo']);

        return User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.com',
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function makeServiceGroup(): Group
    {
        $group = Group::create(['description' => 'Servicios del hogar']);
        config(['catalog.service_group_id' => $group->id]);

        return $group;
    }

    private function makeProvider(string $name, bool $isActive, bool $inServiceGroup): Provider
    {
        $type = TypeUser::firstOrCreate(['description' => 'Prestador']);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        $owner = User::create([
            'name' => $name.' (dueño)',
            'email' => uniqid('prov').'@example.com',
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);

        $provider = Provider::create([
            'user_id' => $owner->id,
            'business_name' => $name,
            'description' => 'Descripción de '.$name,
            'zone' => 'Centro',
            'is_active' => $isActive,
            'is_verified' => true,
        ]);

        $groupId = $inServiceGroup
            ? (int) config('catalog.service_group_id')
            : Group::create(['description' => 'Otro grupo'.uniqid()])->id;

        $subgroup = SubGroup::create([
            'description' => 'Rubro '.$name,
            'group_id' => $groupId,
        ]);
        $provider->subgroups()->attach($subgroup->id);

        return $provider;
    }
}

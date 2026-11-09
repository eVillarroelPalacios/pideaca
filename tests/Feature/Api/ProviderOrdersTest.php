<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProviderOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            $paths = ServiceOrder::query()->pluck('completion_photo_path')->filter();

            foreach ($paths->unique() as $path) {
                $file = public_path('images/servicios/'.$path);

                if (is_file($file)) {
                    @unlink($file);
                }

                $this->waitUntilNameIsFree($file);
            }
        } finally {
            parent::tearDown();
        }
    }

    private function waitUntilNameIsFree(string $file): void
    {
        for ($i = 0; $i < 25; $i++) {
            $probe = tempnam(sys_get_temp_dir(), 'namecheck');
            file_put_contents($probe, 'x');

            if (@rename($probe, $file)) {
                @unlink($file);
                return;
            }

            @unlink($probe);
            usleep(200000);
        }
    }

    public function test_provider_endpoints_require_a_token(): void
    {
        $this->getJson('/api/mobile/v1/provider/orders')->assertStatus(401);
        $this->postJson('/api/mobile/v1/provider/orders/1/accept')->assertStatus(401);
        $this->postJson('/api/mobile/v1/provider/orders/1/complete')->assertStatus(401);
    }

    public function test_client_without_commerce_gets_403(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders')
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'NOT_A_PROVIDER']);
    }

    public function test_index_lists_pool_and_mine(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $pool = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $subgroup->id);
        $mine = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_ACCEPTED, $subgroup->id);
        $mine->merchant_id = $provider->id;
        $mine->save();

        // De otro rubro: no aparece.
        $otherSubgroup = SubGroup::create(['description' => 'Otro rubro', 'group_id' => $subgroup->group_id]);
        $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $otherSubgroup->id);

        // Tomada por otro comercio: no aparece.
        $rival = $this->makeProvider($this->makeProviderOwner('rival@example.com'));
        $taken = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_PROVIDER_DISPATCHED, $subgroup->id);
        $taken->merchant_id = $rival->id;
        $taken->save();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.provider.business_name', $provider->business_name);

        $ids = collect($response->json('data.orders'))->pluck('id')->all();
        $this->assertContains($pool->id, $ids);
        $this->assertContains($mine->id, $ids);
    }

    public function test_index_mine_filter(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $subgroup->id);
        $mine = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_ACCEPTED, $subgroup->id);
        $mine->merchant_id = $provider->id;
        $mine->save();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.orders.0.id', $mine->id);
    }

    public function test_show_pool_mine_and_foreign(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $pool = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $subgroup->id);
        $mine = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_ACCEPTED, $subgroup->id);
        $mine->merchant_id = $provider->id;
        $mine->save();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders/'.$pool->id)
            ->assertOk()
            ->assertJsonPath('data.order.id', $pool->id);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders/'.$mine->id)
            ->assertOk()
            ->assertJsonPath('data.order.id', $mine->id);

        // De otro comercio.
        $rival = $this->makeProvider($this->makeProviderOwner('rival@example.com'));
        $taken = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_PROVIDER_DISPATCHED, $subgroup->id);
        $taken->merchant_id = $rival->id;
        $taken->save();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders/'.$taken->id)
            ->assertStatus(404);

        // Del pool pero de un rubro que no ofrezco.
        $otherSubgroup = SubGroup::create(['description' => 'Otro rubro', 'group_id' => $subgroup->group_id]);
        $foreign = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $otherSubgroup->id);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/mobile/v1/provider/orders/'.$foreign->id)
            ->assertStatus(403);
    }

    public function test_accept_pool_emergency_dispatches_it(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['EMERGENCY']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $subgroup->id);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$order->id.'/accept')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED')
            ->assertJsonPath('data.order.merchant_id', $provider->id);
    }

    public function test_accept_pool_scheduled_confirms_it(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['SCHEDULED']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_REQUESTED, $subgroup->id);
        $order->scheduled_at = now()->addDay();
        $order->save();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$order->id.'/accept')
            ->assertOk()
            ->assertJsonPath('data.order.status', 'ACCEPTED');
    }

    public function test_accept_rejects_taken_or_foreign_orders(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['EMERGENCY']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        // Ya la tomó otro.
        $rival = $this->makeProvider($this->makeProviderOwner('rival@example.com'), ['EMERGENCY']);
        $taken = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_PROVIDER_DISPATCHED, $subgroup->id);
        $taken->merchant_id = $rival->id;
        $taken->save();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$taken->id.'/accept')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'ALREADY_TAKEN']);

        // Rubro que no ofrezco.
        $otherSubgroup = SubGroup::create(['description' => 'Otro rubro', 'group_id' => $subgroup->group_id]);
        $foreign = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_REQUESTED, $otherSubgroup->id);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$foreign->id.'/accept')
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'NOT_IN_SUBGROUP']);
    }

    public function test_dispatch_accepted_scheduled(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['SCHEDULED']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_ACCEPTED, $subgroup->id);
        $order->merchant_id = $provider->id;
        $order->save();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$order->id.'/dispatch')
            ->assertOk()
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED');
    }

    public function test_estimate_moves_to_quoted(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['EMERGENCY']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_PROVIDER_DISPATCHED, $subgroup->id);
        $order->merchant_id = $provider->id;
        $order->save();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$order->id.'/estimate', [
                'additional_cost' => 2500,
                'details' => 'Repuesto incluido',
            ])
            ->assertOk()
            ->assertJsonPath('data.order.status', 'QUOTED');

        $this->assertSame(2500.0, (float) $order->fresh()->additional_cost);
    }

    public function test_propose_creates_and_updates_the_proposal(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['QUOTE']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE, ServiceOrder::STATUS_REQUESTED, $subgroup->id);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$order->id.'/propose', [
                'proposed_price' => 15000,
                'estimated_time' => '2 horas',
                'details' => 'Todo incluido',
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertSame('QUOTED', $order->fresh()->status);
        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $order->id,
            'provider_id' => $provider->id,
            'proposed_price' => 15000,
        ]);

        // Proponer de nuevo actualiza la misma fila.
        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/provider/orders/'.$order->id.'/propose', [
                'proposed_price' => 14000,
                'estimated_time' => '2 horas',
            ])
            ->assertCreated();

        $this->assertSame(1, ServiceQuote::where('service_order_id', $order->id)
            ->where('provider_id', $provider->id)->count());
        $this->assertSame(14000.0, (float) ServiceQuote::where('service_order_id', $order->id)
            ->where('provider_id', $provider->id)->first()->proposed_price);
    }

    public function test_complete_with_photo(): void
    {
        $owner = $this->makeProviderOwner();
        // Orden previa para que esta subida no reuse el nombre del test anterior.
        $provider = $this->makeProvider($owner, ['SCHEDULED'], withDummyOrder: true);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_PROVIDER_DISPATCHED, $subgroup->id);
        $order->merchant_id = $provider->id;
        $order->save();

        $this->actingAs($owner, 'sanctum')
            ->post('/api/mobile/v1/provider/orders/'.$order->id.'/complete', [
                'photo' => UploadedFile::fake()->image('terminado.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('data.order.status', 'COMPLETED');

        $this->assertFileExists(public_path('images/servicios/'.$order->fresh()->completion_photo_path));
    }

    public function test_customer_approves_estimate_and_confirms_release(): void
    {
        $owner = $this->makeProviderOwner();
        $provider = $this->makeProvider($owner, ['EMERGENCY']);
        $subgroup = $this->makeServiceGroup($provider);
        $client = $this->makeClient();

        $order = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY, ServiceOrder::STATUS_QUOTED, $subgroup->id);
        $order->merchant_id = $provider->id;
        $order->additional_cost = 2500;
        $order->total_amount = 2500;
        $order->save();

        // Aprobar el estimado pasa a IN_PROGRESS.
        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/approve-estimate')
            ->assertOk()
            ->assertJsonPath('data.order.status', 'IN_PROGRESS');

        // Completar como prestador y liberar como cliente.
        $order->refresh();
        $order->status = ServiceOrder::STATUS_COMPLETED;
        $order->save();

        $balanceBefore = (float) $provider->fresh()->balance;

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/confirm-release')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('RELEASED', $order->fresh()->escrow_status);
        $this->assertGreaterThan($balanceBefore, (float) $response->json('data.release.provider_balance'));
        $this->assertSame(2500.0, (float) $response->json('data.release.gross'));

        // Segunda vez ya no hay nada retenido.
        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/confirm-release')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'ESCROW_NOT_HOLDING']);
    }

    private function makeClient(string $email = 'cliente@example.com'): User
    {
        $type = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => 'Cliente '.$email,
            'email' => $email,
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function makeProviderOwner(string $email = 'dueno@example.com'): User
    {
        $type = TypeUser::firstOrCreate(['description' => 'Prestador']);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => 'Dueño '.$email,
            'email' => $email,
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123'
        ]);
    }

    private function makeProvider(User $owner, array $typeCodes = ['EMERGENCY'], bool $withDummyOrder = false): Provider
    {
        $provider = Provider::create([
            'user_id' => $owner->id,
            'business_name' => 'Prestador de '.$owner->email,
            'is_active' => true,
            'is_verified' => true,
        ]);

        foreach ($typeCodes as $code) {
            $provider->serviceTypes()->attach(
                ServiceType::firstOrCreate(['code' => $code], ['name' => $code])->id
            );
        }

        if ($withDummyOrder) {
            $subgroup = SubGroup::firstOrCreate(
                ['description' => 'Rubro temporal'],
                ['group_id' => Group::create(['description' => 'Grupo temporal '.uniqid()])->id]
            );

            ServiceOrder::create([
                'customer_id' => $owner->id,
                'sub_group_id' => $subgroup->id,
                'order_type' => ServiceOrder::TYPE_EMERGENCY,
                'status' => ServiceOrder::STATUS_CANCELLED,
                'escrow_status' => ServiceOrder::ESCROW_HOLDING,
            ]);
        }

        return $provider;
    }

    private function makeServiceGroup(?Provider $provider = null): SubGroup
    {
        $group = Group::create(['description' => 'Servicios del hogar']);
        config(['catalog.service_group_id' => $group->id]);

        $subgroup = SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);

        if ($provider) {
            $provider->subgroups()->attach($subgroup->id);
        }

        return $subgroup;
    }

    private function makeOrder(User $client, string $type, string $status, int $subgroupId): ServiceOrder
    {
        return ServiceOrder::create([
            'customer_id' => $client->id,
            'sub_group_id' => $subgroupId,
            'order_type' => $type,
            'status' => $status,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
            'description' => 'Orden de prueba',
        ]);
    }
}

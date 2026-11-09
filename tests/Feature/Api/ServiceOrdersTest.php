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

class ServiceOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            $paths = ServiceOrder::query()->pluck('photo_path')
                ->merge(ServiceOrder::query()->pluck('audio_path'))
                ->filter();

            foreach (ServiceQuote::query()->pluck('attachments_json') as $attachments) {
                foreach ((array) $attachments as $attachment) {
                    if (! empty($attachment['path'])) {
                        $paths->push($attachment['path']);
                    }
                }
            }

            foreach ($paths->filter()->unique() as $path) {
                $file = public_path('images/servicios/'.$path);

                if (is_file($file)) {
                    @unlink($file);
                }

                // En Windows el nombre puede quedar reservado unos instantes
                // (el antivirus/indexador mantiene un handle del jpg recién
                // borrado). Como los nombres derivan del id y la BD se
                // reinicia en cada test, el siguiente test reutiliza el mismo
                // nombre: esperamos a que vuelva a estar libre.
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

    public function test_service_endpoints_require_a_token(): void
    {
        $this->getJson('/api/mobile/v1/service-subgroups')->assertStatus(401);
        $this->getJson('/api/mobile/v1/service-orders')->assertStatus(401);
        $this->postJson('/api/mobile/v1/service-orders/emergency')->assertStatus(401);
        $this->postJson('/api/mobile/v1/service-orders/scheduled')->assertStatus(401);
        $this->postJson('/api/mobile/v1/service-orders/quote')->assertStatus(401);
    }

    public function test_subgroups_lists_only_hogar_subgroups(): void
    {
        $client = $this->makeClient();
        $plomeria = $this->makeServiceGroup(withExtraGroup: true);
        $this->makeProvider('Plomero Juan')->subgroups()->attach($plomeria->id);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/service-subgroups')
            ->assertOk()
            ->assertJson(['success' => true]);

        $data = collect($response->json('data'));
        $plomeria = $data->firstWhere('description', 'Plomería');
        $this->assertNotNull($plomeria);
        $this->assertSame(1, $plomeria['providers_count']);
        $this->assertNull($data->firstWhere('description', 'Panadería'));
    }

    public function test_emergency_requires_a_photo(): void
    {
        $client = $this->makeClient();
        $subgroup = $this->makeServiceGroup();

        $this->actingAs($client, 'sanctum')
            ->post('/api/mobile/v1/service-orders/emergency', [
                'sub_group_id' => $subgroup->id,
                'description' => 'Se rompió un caño',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_emergency_creates_an_order_to_the_pool(): void
    {
        $client = $this->makeClient();
        $subgroup = $this->makeServiceGroup();

        $response = $this->actingAs($client, 'sanctum')
            ->post('/api/mobile/v1/service-orders/emergency', [
                'sub_group_id' => $subgroup->id,
                'description' => 'Se rompió un caño',
                'address' => 'Calle Falsa 123',
                'payment_method' => 'efectivo',
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $orderId = $response->json('data.order.id');
        $this->assertNotEmpty($orderId);
        $this->assertSame('EMERGENCY', $response->json('data.order.order_type'));
        $this->assertSame('REQUESTED', $response->json('data.order.status'));

        $order = ServiceOrder::find($orderId);
        $this->assertSame($client->id, $order->customer_id);
        $this->assertNull($order->merchant_id);
        $this->assertNotEmpty($order->photo_path);
        $this->assertFileExists(public_path('images/servicios/'.$order->photo_path));
    }

    public function test_emergency_to_a_direct_provider_assigns_it(): void
    {
        $client = $this->makeClient();
        $subgroup = $this->makeServiceGroup();
        $provider = $this->makeProvider('Plomero Juan');

        // Los nombres de archivo derivan del id y la BD se reinicia en cada
        // test: ocupamos el id 1 con una orden sin archivos para que esta
        // subida use urgencia_2_foto.jpg y no choque con el test anterior
        // (en Windows el nombre recién borrado queda bloqueado un rato).
        ServiceOrder::create([
            'customer_id' => $client->id,
            'sub_group_id' => $subgroup->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_CANCELLED,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->post('/api/mobile/v1/service-orders/emergency', [
                'provider_id' => $provider->id,
                'description' => 'Pierde la canilla',
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertCreated();

        $this->assertSame($provider->id, $response->json('data.order.merchant_id'));
        $this->assertSame('Plomero Juan', $response->json('data.order.merchant.business_name'));
    }

    public function test_emergency_rejects_a_provider_outside_the_hogar_group(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();
        $outsider = $this->makeProvider('Panadería Rica', inServiceGroup: false);

        $this->actingAs($client, 'sanctum')
            ->post('/api/mobile/v1/service-orders/emergency', [
                'provider_id' => $outsider->id,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(404);
    }

    public function test_scheduled_creates_an_appointment(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $when = now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d H:i');

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/scheduled', [
                'provider_id' => $this->makeProvider('Electricista Ana', true, ['SCHEDULED'])->id,
                'scheduled_at' => $when,
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'Efectivo',
                'payment_mode' => 'FULL',
                'notes' => 'Revisar el tablero',
                'address_street' => 'Calle Falsa 123',
                'address_neighborhood' => 'Centro',
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertSame('SCHEDULED', $response->json('data.order.order_type'));
        $this->assertSame('AT_HOME', $response->json('data.order.fulfillment'));
        $this->assertSame('Calle Falsa 123, Centro', $response->json('data.order.address'));
    }

    public function test_scheduled_rejects_a_past_date(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/scheduled', [
                'provider_id' => $this->makeProvider('Electricista Ana', true, ['SCHEDULED'])->id,
                'scheduled_at' => now()->subHour()->format('Y-m-d H:i'),
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'Efectivo',
                'payment_mode' => 'FULL',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_quote_creates_a_request_with_photos(): void
    {
        $client = $this->makeClient();
        $subgroup = $this->makeServiceGroup();

        $response = $this->actingAs($client, 'sanctum')
            ->post('/api/mobile/v1/service-orders/quote', [
                'sub_group_id' => $subgroup->id,
                'details' => 'Quiero renovar el baño completo',
                'photos' => [
                    UploadedFile::fake()->image('bano1.jpg'),
                    UploadedFile::fake()->image('bano2.jpg'),
                ],
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $orderId = $response->json('data.order.id');
        $this->assertSame('QUOTE', $response->json('data.order.order_type'));
        $this->assertCount(2, $response->json('data.order.attachments'));

        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $orderId,
            'provider_id' => null,
            'status' => 'PENDING',
        ]);
    }

    public function test_index_lists_only_my_orders_with_filters(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient('otro@example.com');
        $this->makeServiceGroup();

        $mine = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY);
        $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED);
        $this->makeOrder($other, ServiceOrder::TYPE_EMERGENCY);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/service-orders')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meta.total', 2);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($mine->id, $ids);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/service-orders?order_type=SCHEDULED')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_type', 'SCHEDULED');
    }

    public function test_show_and_cancel(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient('otro@example.com');
        $this->makeServiceGroup();

        $mine = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED);
        $foreign = $this->makeOrder($other, ServiceOrder::TYPE_SCHEDULED);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/service-orders/'.$mine->id)
            ->assertOk()
            ->assertJsonPath('data.order.id', $mine->id)
            ->assertJsonPath('data.order.cancellable', true);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/service-orders/'.$foreign->id)
            ->assertStatus(404);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$mine->id.'/cancel')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.order.status', 'CANCELLED');

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$foreign->id.'/cancel')
            ->assertStatus(404);
    }

    public function test_cancel_rejects_a_finished_order(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();

        $done = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY);
        $done->status = ServiceOrder::STATUS_COMPLETED;
        $done->save();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$done->id.'/cancel')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'ORDER_NOT_CANCELLABLE']);
    }

    public function test_accept_quote_confirms_the_order_with_the_chosen_proposal(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();
        $chosen = $this->makeProvider('Plomero Juan');
        $other = $this->makeProvider('Plomero Pedro');

        $order = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE, ServiceOrder::STATUS_QUOTED);
        $proposal = $this->makeProposal($order, $chosen, 15000);
        $second = $this->makeProposal($order, $other, 18000);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/accept-quote', [
                'quote' => $proposal->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('ACCEPTED', $response->json('data.order.status'));
        $this->assertSame($chosen->id, $response->json('data.order.merchant_id'));
        $this->assertSame('ACCEPTED', $response->json('data.quote.status'));
        $this->assertSame($proposal->id, $response->json('data.quote.id'));

        $this->assertSame('ACCEPTED', $proposal->fresh()->status);
        $this->assertSame('REJECTED', $second->fresh()->status);
        $this->assertSame($chosen->id, $order->fresh()->merchant_id);
    }

    public function test_accept_quote_uses_the_latest_proposal_by_default(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();
        $first = $this->makeProvider('Plomero Juan');
        $latest = $this->makeProvider('Plomero Pedro');

        $order = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE, ServiceOrder::STATUS_QUOTED);
        $this->makeProposal($order, $first, 15000);
        $newest = $this->makeProposal($order, $latest, 18000);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/accept-quote')
            ->assertOk()
            ->assertJsonPath('data.quote.id', $newest->id)
            ->assertJsonPath('data.order.merchant_id', $latest->id);
    }

    public function test_accept_quote_rejects_invalid_states(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient('otro@example.com');
        $this->makeServiceGroup();

        // Todavía no hay propuestas (sigue en REQUESTED).
        $fresh = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$fresh->id.'/accept-quote')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'NO_PENDING_QUOTE']);

        // Orden ajena no se revela.
        $foreign = $this->makeOrder($other, ServiceOrder::TYPE_QUOTE, ServiceOrder::STATUS_QUOTED);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$foreign->id.'/accept-quote')
            ->assertStatus(404);

        // No es del flujo de presupuestos.
        $scheduled = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$scheduled->id.'/accept-quote')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'NOT_A_QUOTE']);
    }

    public function test_reject_quote_keeps_the_order_open(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();
        $provider = $this->makeProvider('Plomero Juan');

        $order = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE, ServiceOrder::STATUS_QUOTED);
        $proposal = $this->makeProposal($order, $provider, 15000);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/reject-quote', [
                'quote' => $proposal->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.quote.status', 'REJECTED')
            ->assertJsonPath('data.order.status', 'QUOTED');

        $this->assertSame('REJECTED', $proposal->fresh()->status);
        $this->assertSame('QUOTED', $order->fresh()->status);
    }

    public function test_customer_rates_a_completed_job(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();
        $provider = $this->makeProvider('Plomero Juan');

        $order = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_COMPLETED);
        $order->merchant_id = $provider->id;
        $order->save();

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/rate', [
                'stars' => 5,
                'comment' => 'Excelente trabajo',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(5, $response->json('data.rating.stars'));
        $this->assertSame(5.0, (float) $response->json('data.provider_rating'));
        $this->assertTrue($response->json('data.order.customer_rated'));

        // No se puede calificar dos veces.
        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/rate', ['stars' => 3])
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'ALREADY_RATED']);
    }

    public function test_rate_rejects_unfinished_or_foreign_orders(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient('otro@example.com');
        $this->makeServiceGroup();

        $open = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$open->id.'/rate', ['stars' => 5])
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => 'ORDER_NOT_COMPLETED']);

        $foreign = $this->makeOrder($other, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_COMPLETED);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$foreign->id.'/rate', ['stars' => 5])
            ->assertStatus(404);
    }

    public function test_provider_rates_the_customer(): void
    {
        $client = $this->makeClient();
        $this->makeServiceGroup();
        $provider = $this->makeProvider('Plomero Juan');
        $owner = User::find($provider->user_id);

        $order = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_COMPLETED);
        $order->merchant_id = $provider->id;
        $order->save();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/rate', ['stars' => 4])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.rating.stars', 4);

        $this->assertTrue($order->fresh()->ratings()->where('reviewer_id', $owner->id)->exists());
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

    private function makeServiceGroup(bool $withExtraGroup = false): SubGroup
    {
        $group = Group::create(['description' => 'Servicios del hogar']);
        config(['catalog.service_group_id' => $group->id]);

        if ($withExtraGroup) {
            $other = Group::create(['description' => 'Otro grupo'.uniqid()]);
            SubGroup::create(['description' => 'Panadería', 'group_id' => $other->id]);
        }

        return SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);
    }

    private function makeProvider(string $name, bool $inServiceGroup = true, array $typeCodes = ['EMERGENCY']): Provider
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
            'is_active' => true,
            'is_verified' => true,
        ]);

        $groupId = $inServiceGroup
            ? (int) config('catalog.service_group_id')
            : Group::create(['description' => 'Extra '.uniqid()])->id;

        $provider->subgroups()->attach(
            SubGroup::create(['description' => 'Rubro '.$name.uniqid(), 'group_id' => $groupId])->id
        );
        foreach ($typeCodes as $code) {
            $provider->serviceTypes()->attach(
                ServiceType::firstOrCreate(['code' => $code], ['name' => $code])->id
            );
        }

        return $provider;
    }

    private function makeOrder(User $client, string $type, string $status = ServiceOrder::STATUS_REQUESTED): ServiceOrder
    {
        return ServiceOrder::create([
            'customer_id' => $client->id,
            'sub_group_id' => SubGroup::first()->id,
            'order_type' => $type,
            'status' => $status,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
            'description' => 'Orden de prueba',
        ]);
    }

    private function makeProposal(ServiceOrder $order, Provider $provider, float $price): ServiceQuote
    {
        return ServiceQuote::create([
            'service_order_id' => $order->id,
            'provider_id' => $provider->id,
            'proposed_price' => $price,
            'details' => 'Propuesta de prueba',
            'status' => ServiceQuote::STATUS_PENDING,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceCompletionEscrowRatingTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $prestadorType;

    private TypeUser $clienteType;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private Provider $otherProvider;

    private User $customer;

    private User $secondCustomer;

    private SubGroup $subgroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestadorType = TypeUser::create(['description' => 'Prestador']);
        $this->clienteType = TypeUser::create(['description' => 'Cliente']);
        UserStatus::create(['status' => 'Activo']);

        $this->providerUser = User::create([
            'name' => 'Mariana Prestadora',
            'email' => 'mariana@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Mariana Hogar',
            'rating' => 4,
            'balance' => 0,
            'is_active' => true,
        ]);

        $this->otherProviderUser = User::create([
            'name' => 'Pedro Prestador',
            'email' => 'pedro@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        $this->otherProvider = Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Pedro Servicios',
            'rating' => 4,
            'balance' => 0,
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Lucia Cliente',
            'email' => 'lucia@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);

        $this->secondCustomer = User::create([
            'name' => 'Ana Cliente',
            'email' => 'ana@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Plomeros', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
        $this->otherProvider->subgroups()->attach($this->subgroup->id);
    }

    protected function tearDown(): void
    {
        try {
            $paths = ServiceOrder::query()->pluck('completion_photo_path')->filter();

            foreach ($paths as $path) {
                $file = public_path('images/servicios/'.$path);

                if (is_file($file)) {
                    @unlink($file);
                }
            }

            $dir = public_path('images/servicios');

            if (is_dir($dir) && count(scandir($dir)) <= 2) {
                @rmdir($dir);
            }
        } catch (\Throwable $e) {
            // El cleanup no debe enmascarar el resultado del test.
        }

        parent::tearDown();
    }

    // ------------------------------------------------------------- seguridad

    public function test_invitado_recibe_401_en_cierre_liberacion_y_calificacion(): void
    {
        $orden = $this->cita();

        $this->postJson('/api/v1/provider/service-orders/'.$orden->id.'/complete', [])->assertStatus(401);
        $this->postJson('/api/v1/customer/service-orders/'.$orden->id.'/confirm-release', [])->assertStatus(401);
        $this->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', [])->assertStatus(401);
        $this->postJson('/api/v1/provider/service-orders/'.$orden->id.'/rate', [])->assertStatus(401);
    }

    public function test_prestador_ajeno_no_puede_completar_la_orden(): void
    {
        $orden = $this->cita();

        $this->actingAs($this->otherProviderUser)
            ->post('/api/v1/provider/service-orders/'.$orden->id.'/complete', [
                'photo' => UploadedFile::fake()->image('ajeno.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(404);

        $this->assertSame('ACCEPTED', $orden->fresh()->status);
    }

    public function test_otro_cliente_no_puede_liberar_ni_calificar(): void
    {
        $orden = $this->completada();

        $this->actingAs($this->secondCustomer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/confirm-release', [])
            ->assertStatus(404);

        $this->actingAs($this->secondCustomer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', ['stars' => 5])
            ->assertStatus(404);

        $this->actingAs($this->secondCustomer)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/rate', ['stars' => 5])
            ->assertStatus(404);

        $this->assertDatabaseCount('service_ratings', 0);
        $this->assertSame('HOLDING', $orden->fresh()->escrow_status);
    }

    // --------------------------------------------------- cierre del trabajo

    public function test_prestador_completa_el_trabajo_con_foto_comprobante(): void
    {
        $orden = $this->cita();

        $respuesta = $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$orden->id.'/complete', [
                'photo' => UploadedFile::fake()->image('terminado.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'COMPLETED')
            ->assertJsonPath('data.order.status_label', 'Completado')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING');

        $this->assertStringContainsString('completada_', $respuesta->json('data.order.completion_photo_url'));
        $this->assertNotNull($respuesta->json('data.order.completed_at'));

        $orden = $orden->fresh();

        $this->assertSame('COMPLETED', $orden->status);
        $this->assertNotNull($orden->completed_at);
        $this->assertNotNull($orden->completion_photo_path);
        $this->assertFileExists(public_path('images/servicios/'.$orden->completion_photo_path));
    }

    public function test_completar_rechazado_por_estado_o_cotizacion_pendiente(): void
    {
        $solicitada = $this->urgencia(['status' => ServiceOrder::STATUS_REQUESTED]);

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$solicitada->id.'/complete', [
                'photo' => UploadedFile::fake()->image('ok.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('status', 'REQUESTED');

        $presupuestada = $this->urgencia(['status' => ServiceOrder::STATUS_QUOTED, 'additional_cost' => 1500]);

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$presupuestada->id.'/complete', [
                'photo' => UploadedFile::fake()->image('ok.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('status', 'QUOTED');

        $cancelada = $this->cita(['status' => ServiceOrder::STATUS_CANCELLED]);

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$cancelada->id.'/complete', [
                'photo' => UploadedFile::fake()->image('ok.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('status', 'CANCELLED');

        $this->assertSame('REQUESTED', $solicitada->fresh()->status);
        $this->assertNull($solicitada->fresh()->completion_photo_path);
    }

    public function test_completar_rechazado_sin_foto_o_con_archivo_invalido(): void
    {
        $orden = $this->cita();

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$orden->id.'/complete', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$orden->id.'/complete', [
                'photo' => UploadedFile::fake()->create('acta.txt', 10, 'text/plain'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->assertSame('ACCEPTED', $orden->fresh()->status);
    }

    // ------------------------------------------------- liberacion de escrow

    public function test_cliente_libera_el_pago_con_comision_y_balance_del_prestador(): void
    {
        $this->provider->update(['balance' => 5000]);
        $orden = $this->completada();

        $respuesta = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/confirm-release', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.escrow_status', 'RELEASED')
            ->assertJsonPath('data.order.commission_amount', '2000.00')
            ->assertJsonPath('data.release.gross', '20000.00')
            ->assertJsonPath('data.release.commission_amount', '2000.00')
            ->assertJsonPath('data.release.net_amount', '18000.00')
            ->assertJsonPath('data.release.provider_balance', '23000.00');

        $this->assertEquals(10, $respuesta->json('data.release.commission_percent'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'COMPLETED',
            'escrow_status' => 'RELEASED',
            'commission_amount' => '2000.00',
        ]);

        $this->assertDatabaseHas('providers', [
            'id' => $this->provider->id,
            'balance' => '23000.00',
        ]);
    }

    public function test_liberacion_rechazada_si_no_esta_completada_o_ya_esta_liberada(): void
    {
        $pendiente = $this->cita();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$pendiente->id.'/confirm-release', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'ACCEPTED');

        $liberada = $this->completada(['escrow_status' => ServiceOrder::ESCROW_RELEASED]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$liberada->id.'/confirm-release', [])
            ->assertStatus(409)
            ->assertJsonPath('escrow_status', 'RELEASED');

        $this->assertDatabaseHas('providers', [
            'id' => $this->provider->id,
            'balance' => '0.00',
        ]);
    }

    public function test_cierre_y_liberacion_de_urgencia_con_estimado_aprobado(): void
    {
        $urgencia = $this->urgencia([
            'status' => ServiceOrder::STATUS_IN_PROGRESS,
            'additional_cost' => 1500,
            'total_amount' => 1500,
        ]);

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$urgencia->id.'/complete', [
                'photo' => UploadedFile::fake()->image('urgencia-resuelta.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'COMPLETED')
            ->assertJsonPath('data.order.total_amount', '1500.00');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$urgencia->id.'/confirm-release', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.escrow_status', 'RELEASED')
            ->assertJsonPath('data.release.gross', '1500.00')
            ->assertJsonPath('data.release.commission_amount', '150.00')
            ->assertJsonPath('data.release.net_amount', '1350.00')
            ->assertJsonPath('data.release.provider_balance', '1350.00');
    }

    // --------------------------------------------------------- calificaciones

    public function test_cliente_califica_al_prestador_y_actualiza_su_rating_publico(): void
    {
        $orden = $this->completada();

        $respuesta = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', [
                'stars' => 5,
                'comment' => 'Excelente trabajo, muy prolijo.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating.stars', 5)
            ->assertJsonPath('data.rating.comment', 'Excelente trabajo, muy prolijo.')
            ->assertJsonPath('data.rating.reviewer_id', $this->customer->id)
            ->assertJsonPath('data.rating.ratee_id', $this->providerUser->id)
            ->assertJsonPath('data.provider_rating', '5.00');

        $this->assertDatabaseHas('service_ratings', [
            'service_order_id' => $orden->id,
            'reviewer_id' => $this->customer->id,
            'ratee_id' => $this->providerUser->id,
            'stars' => 5,
        ]);

        $this->assertDatabaseHas('providers', [
            'id' => $this->provider->id,
            'rating' => '5.00',
        ]);

        $this->assertNotNull($respuesta->json('data.rating.id'));
    }

    public function test_prestador_califica_al_cliente_sin_tocar_su_rating_publico(): void
    {
        $this->provider->update(['rating' => 4.5]);
        $orden = $this->completada();

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/rate', [
                'stars' => 4,
                'comment' => 'Cliente puntual y claro con las indicaciones.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rating.stars', 4)
            ->assertJsonPath('data.rating.reviewer_id', $this->providerUser->id)
            ->assertJsonPath('data.rating.ratee_id', $this->customer->id)
            ->assertJsonPath('data.provider_rating', null);

        $this->assertDatabaseHas('service_ratings', [
            'service_order_id' => $orden->id,
            'reviewer_id' => $this->providerUser->id,
            'ratee_id' => $this->customer->id,
            'stars' => 4,
        ]);

        $this->assertDatabaseHas('providers', [
            'id' => $this->provider->id,
            'rating' => '4.50',
        ]);
    }

    public function test_calificacion_rechazada_si_no_esta_completada(): void
    {
        $orden = $this->cita();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', ['stars' => 5])
            ->assertStatus(409)
            ->assertJsonPath('status', 'ACCEPTED');

        $this->assertDatabaseCount('service_ratings', 0);
    }

    public function test_calificacion_valida_un_solo_comentario_por_autor(): void
    {
        $orden = $this->completada();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', ['stars' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stars']);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', ['stars' => 6])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stars']);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stars']);

        $this->assertDatabaseCount('service_ratings', 0);
    }

    public function test_calificacion_duplicada_es_409(): void
    {
        $orden = $this->completada();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', ['stars' => 5])
            ->assertStatus(200);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', ['stars' => 1])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        // El prestador puede calificar a la misma orden: es otro autor.
        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/rate', ['stars' => 5])
            ->assertStatus(200);

        $this->assertDatabaseCount('service_ratings', 2);
    }

    // ------------------------------------------------------------- e2e

    public function test_flujo_completo_de_cierre_liberacion_y_calificaciones(): void
    {
        $orden = $this->cita();

        $this->actingAs($this->providerUser)
            ->post('/api/v1/provider/service-orders/'.$orden->id.'/complete', [
                'photo' => UploadedFile::fake()->image('trabajo-terminado.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'COMPLETED')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/confirm-release', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.escrow_status', 'RELEASED')
            ->assertJsonPath('data.release.net_amount', '18000.00')
            ->assertJsonPath('data.release.provider_balance', '18000.00');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/rate', [
                'stars' => 5,
                'comment' => 'Impecable.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.provider_rating', '5.00');

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/rate', [
                'stars' => 4,
                'comment' => 'Muy buena experiencia.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'COMPLETED',
            'escrow_status' => 'RELEASED',
            'commission_amount' => '2000.00',
        ]);

        $this->assertDatabaseHas('providers', [
            'id' => $this->provider->id,
            'balance' => '18000.00',
            'rating' => '5.00',
        ]);

        $this->assertDatabaseCount('service_ratings', 2);
        $this->assertNotNull($orden->fresh()->completion_photo_path);
        $this->assertFileExists(public_path('images/servicios/'.$orden->fresh()->completion_photo_path));
    }

    // ------------------------------------------------------------- helpers

    private function cita(array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_ACCEPTED,
            'scheduled_at' => Carbon::now()->addDays(2)->setTime(9, 0),
            'fulfillment' => 'AT_HOME',
            'payment_method' => 'cash',
            'payment_mode' => 'DEPOSIT',
            'deposit_amount' => 10000,
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 20000,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ], $overrides));
    }

    private function urgencia(array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_REQUESTED,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 0,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ], $overrides));
    }

    private function completada(array $overrides = []): ServiceOrder
    {
        return $this->cita(array_merge([
            'status' => ServiceOrder::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
            'completion_photo_path' => 'completada_prueba_foto.jpg',
        ], $overrides));
    }
}

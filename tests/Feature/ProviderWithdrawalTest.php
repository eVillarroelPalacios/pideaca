<?php

namespace Tests\Feature;

use App\Models\DriverWallet;
use App\Models\DriverWithdrawalRequest;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderDriver;
use App\Models\TypeUser;
use App\Models\User;
use App\Services\DriverWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El comercio recibe y procesa los retiros de sus repartidores.
 *
 * Alcance: solo ve solicitudes de repartidores vinculados a su comercio
 * (con al menos una entrega para él o en su lista de confianza). Solo el
 * titular de la cuenta (o el Admin) puede aprobar/rechazar. El Admin
 * conserva su propio flujo en /admin/withdrawals.
 */
class ProviderWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private DriverWalletService $service;

    private User $owner;

    private Provider $provider;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        config(['fastdelivery.min_withdrawal' => 1000]);

        $this->service = app(DriverWalletService::class);

        $prestador = TypeUser::firstOrCreate(['description' => 'Prestador']);
        $repartidor = TypeUser::firstOrCreate(['description' => 'Repartidor']);

        $this->owner = User::factory()->create([
            'name' => 'Dueño del comercio',
            'type_user_id' => $prestador->id,
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio de prueba',
            'zone' => 'Centro',
        ]);

        $this->driver = User::factory()->create([
            'name' => 'Repartidor vinculado',
            'type_user_id' => $repartidor->id,
        ]);

        DriverWallet::create([
            'user_id' => $this->driver->id,
            'balance' => 5000,
        ]);
    }

    private function deliverOrder(User $driver): void
    {
        $customer = User::factory()->create();

        Order::create([
            'provider_id' => $this->provider->id,
            'user_id' => $customer->id,
            'order_number' => 'PIDE-TEST-'.uniqid(),
            'payment_method' => 'transfer',
            'status' => Order::STATUS_DELIVERED,
            'driver_id' => $driver->id,
        ]);
    }

    public function test_el_comercio_ve_y_aprueba_el_retiro_de_su_repartidor(): void
    {
        $this->deliverOrder($this->driver);
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->owner)
            ->getJson('/profile/provider/withdrawals')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('withdrawals.0.id', $withdrawal->id)
            ->assertJsonPath('withdrawals.0.amount', 1500)
            ->assertJsonPath('withdrawals.0.method', null);

        $this->actingAs($this->owner)
            ->postJson('/profile/provider/withdrawals/'.$withdrawal->id.'/approve')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('withdrawal.status', 'approved');

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_APPROVED,
            $withdrawal->fresh()->status
        );
        $this->assertEqualsWithDelta(
            3500.0,
            (float) DriverWallet::where('user_id', $this->driver->id)->value('balance'),
            0.001
        );
    }

    public function test_el_comercio_ve_al_repartidor_de_confianza_aunque_no_haya_entregado(): void
    {
        ProviderDriver::create([
            'provider_id' => $this->provider->id,
            'user_id' => $this->driver->id,
            'status' => 'active',
        ]);
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1200);

        $this->actingAs($this->owner)
            ->getJson('/profile/provider/withdrawals')
            ->assertOk()
            ->assertJsonPath('withdrawals.0.id', $withdrawal->id);
    }

    public function test_el_comercio_no_ve_ni_procesa_retiros_ajenos(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->owner)
            ->getJson('/profile/provider/withdrawals')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'withdrawals');

        $this->actingAs($this->owner)
            ->postJson('/profile/provider/withdrawals/'.$withdrawal->id.'/approve')
            ->assertStatus(403);

        $this->actingAs($this->owner)
            ->postJson('/profile/provider/withdrawals/'.$withdrawal->id.'/reject', ['reason' => 'x'])
            ->assertStatus(403);

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_PENDING,
            $withdrawal->fresh()->status
        );
    }

    public function test_el_comercio_rechaza_y_el_saldo_vuelve_a_la_billetera(): void
    {
        $this->deliverOrder($this->driver);
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->owner)
            ->postJson('/profile/provider/withdrawals/'.$withdrawal->id.'/reject', [
                'reason' => 'Pagamos por otro medio',
            ])
            ->assertOk()
            ->assertJsonPath('withdrawal.status', 'rejected');

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_REJECTED,
            $withdrawal->fresh()->status
        );
        $this->assertEqualsWithDelta(
            5000.0,
            (float) DriverWallet::where('user_id', $this->driver->id)->value('balance'),
            0.001
        );
    }

    public function test_el_secundario_no_gestiona_retiros(): void
    {
        $this->deliverOrder($this->driver);
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $secondary = User::factory()->create([
            'name' => 'Secundario',
            'email' => $this->owner->email,
            'type_user_id' => $this->owner->type_user_id,
        ]);

        $this->actingAs($secondary)
            ->getJson('/profile/provider/withdrawals')
            ->assertStatus(403);

        $this->actingAs($secondary)
            ->postJson('/profile/provider/withdrawals/'.$withdrawal->id.'/approve')
            ->assertStatus(403);

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_PENDING,
            $withdrawal->fresh()->status
        );
    }

    public function test_un_cliente_no_accede_a_los_retiros_del_comercio(): void
    {
        $clientType = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $client = User::factory()->create(['type_user_id' => $clientType->id]);

        $this->actingAs($client)
            ->getJson('/profile/provider/withdrawals')
            ->assertStatus(403);
    }

    public function test_la_api_movil_expone_y_procesa_retiros_del_comercio(): void
    {
        $this->deliverOrder($this->driver);
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/mobile/v1/profile/provider/withdrawals')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('withdrawals.0.id', $withdrawal->id)
            ->assertJsonPath('withdrawals.0.amount', 1500)
            ->assertJsonPath('withdrawals.0.user.name', 'Repartidor vinculado');

        $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/mobile/v1/profile/provider/withdrawals/'.$withdrawal->id.'/approve')
            ->assertOk()
            ->assertJsonPath('withdrawal.status', 'approved');

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_APPROVED,
            $withdrawal->fresh()->status
        );
    }

    public function test_la_api_movil_no_muestra_retiros_ajenos_al_comercio(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/mobile/v1/profile/provider/withdrawals')
            ->assertOk()
            ->assertJsonCount(0, 'withdrawals');

        $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/mobile/v1/profile/provider/withdrawals/'.$withdrawal->id.'/reject', ['reason' => 'x'])
            ->assertStatus(403);
    }

    public function test_la_api_movil_rechaza_y_devuelve_el_saldo(): void
    {
        $this->deliverOrder($this->driver);
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/mobile/v1/profile/provider/withdrawals/'.$withdrawal->id.'/reject', [
                'reason' => 'Se paga por otro medio',
            ])
            ->assertOk()
            ->assertJsonPath('withdrawal.status', 'rejected');

        $this->assertEqualsWithDelta(
            5000.0,
            (float) DriverWallet::where('user_id', $this->driver->id)->value('balance'),
            0.001
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\DriverWallet;
use App\Models\DriverWithdrawalRequest;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\DriverWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Retiro de saldo del repartidor con aprobacion manual del admin.
 *
 * Regla central: el monto se congela al solicitar (se descuenta de la
 * billetera) para que no pueda retirarse dos veces el mismo dinero. Si el
 * admin rechaza, el monto vuelve a la billetera.
 */
class DriverWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private DriverWalletService $service;

    private User $driver;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['fastdelivery.min_withdrawal' => 1000]);

        $this->service = app(DriverWalletService::class);

        $driverType = TypeUser::firstOrCreate(['description' => 'Repartidor']);
        $adminType = TypeUser::firstOrCreate(['description' => 'Admin']);

        $this->driver = User::factory()->create([
            'name' => 'Repartidor de prueba',
            'type_user_id' => $driverType->id,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin de prueba',
            'type_user_id' => $adminType->id,
        ]);

        DriverWallet::create([
            'user_id' => $this->driver->id,
            'balance' => 5000,
        ]);
    }

    private function balance(): float
    {
        return (float) DriverWallet::where('user_id', $this->driver->id)->value('balance');
    }

    private function pendingCount(): int
    {
        return DriverWithdrawalRequest::where('user_id', $this->driver->id)
            ->where('status', DriverWithdrawalRequest::STATUS_PENDING)
            ->count();
    }

    // ────────────────────────────────────────────────────────────
    // Solicitud: el saldo se congela en el acto
    // ────────────────────────────────────────────────────────────

    public function test_solicitar_un_retiro_congela_el_saldo(): void
    {
        $withdrawal = $this->service->requestWithdrawal(
            $this->driver,
            1500,
            'transferencia',
            'Alias: repartidor.pideaca'
        );

        $this->assertSame(DriverWithdrawalRequest::STATUS_PENDING, $withdrawal->status);
        $this->assertEqualsWithDelta(1500.0, (float) $withdrawal->amount, 0.001);
        $this->assertEqualsWithDelta(3500.0, $this->balance(), 0.001);
        $this->assertSame(1, $this->pendingCount());

        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $withdrawal->wallet_transaction_id,
            'user_id' => $this->driver->id,
            'type' => 'withdrawal',
            'status' => 'pending',
        ]);
    }

    public function test_no_se_puede_retirar_menos_del_minimo(): void
    {
        try {
            $this->service->requestWithdrawal($this->driver, 500);
            $this->fail('Se esperaba una ValidationException por monto mínimo.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->assertEqualsWithDelta(5000.0, $this->balance(), 0.001);
        $this->assertSame(0, $this->pendingCount());
    }

    public function test_no_se_puede_retirar_mas_que_el_saldo(): void
    {
        try {
            $this->service->requestWithdrawal($this->driver, 9000);
            $this->fail('Se esperaba una ValidationException por saldo insuficiente.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->assertEqualsWithDelta(5000.0, $this->balance(), 0.001);
        $this->assertSame(0, $this->pendingCount());
    }

    public function test_no_se_puede_tener_dos_solicitudes_pendientes(): void
    {
        $this->service->requestWithdrawal($this->driver, 1000);

        try {
            $this->service->requestWithdrawal($this->driver, 1000);
            $this->fail('Se esperaba una ValidationException por solicitud pendiente.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->assertEqualsWithDelta(4000.0, $this->balance(), 0.001);
        $this->assertSame(1, $this->pendingCount());
    }

    // ────────────────────────────────────────────────────────────
    // Aprobacion y rechazo
    // ────────────────────────────────────────────────────────────

    public function test_aprobar_marca_la_solicitud_y_la_transaccion(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $approved = $this->service->approve($withdrawal, $this->admin);

        $this->assertSame(DriverWithdrawalRequest::STATUS_APPROVED, $approved->status);
        $this->assertSame($this->admin->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);

        // El saldo ya se habia congelado al solicitar: aprobar no lo toca.
        $this->assertEqualsWithDelta(3500.0, $this->balance(), 0.001);
        $this->assertSame(0, $this->pendingCount());

        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $withdrawal->wallet_transaction_id,
            'status' => 'completed',
        ]);
    }

    public function test_rechazar_devuelve_el_saldo_y_registra_el_movimiento(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $rejected = $this->service->reject($withdrawal, $this->admin, 'Datos de pago incorrectos');

        $this->assertSame(DriverWithdrawalRequest::STATUS_REJECTED, $rejected->status);
        $this->assertSame($this->admin->id, $rejected->reviewed_by);
        $this->assertStringContainsString('Motivo: Datos de pago incorrectos', (string) $rejected->notes);

        $this->assertEqualsWithDelta(5000.0, $this->balance(), 0.001);
        $this->assertSame(0, $this->pendingCount());

        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $withdrawal->wallet_transaction_id,
            'status' => 'cancelled',
        ]);

        $refund = WalletTransaction::where('user_id', $this->driver->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        $this->assertNotNull($refund);
        $this->assertEqualsWithDelta(1500.0, (float) $refund->amount, 0.001);
    }

    public function test_no_se_puede_procesar_dos_veces_la_misma_solicitud(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);
        $this->service->approve($withdrawal, $this->admin);

        try {
            $this->service->approve($withdrawal, $this->admin);
            $this->fail('Se esperaba una ValidationException por solicitud ya procesada.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertEqualsWithDelta(3500.0, $this->balance(), 0.001);
    }

    // ────────────────────────────────────────────────────────────
    // API movil del repartidor
    // ────────────────────────────────────────────────────────────

    public function test_la_api_movil_expone_saldo_e_historial(): void
    {
        $this->service->requestWithdrawal($this->driver, 1200);

        $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/mobile/v1/driver/wallet/withdrawals')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('balance', 3800)
            ->assertJsonPath('min_withdrawal', 1000)
            ->assertJsonPath('withdrawals.0.amount', 1200)
            ->assertJsonPath('withdrawals.0.status', 'pending');
    }

    public function test_la_api_movil_permite_solicitar_el_retiro(): void
    {
        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/mobile/v1/driver/wallet/withdraw', [
                'amount' => 1500,
                'method' => 'transferencia',
                'notes' => 'CBU 0000000000000000000000',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('balance', 3500)
            ->assertJsonPath('withdrawal.status', 'pending');

        $this->assertDatabaseHas('driver_withdrawal_requests', [
            'user_id' => $this->driver->id,
            'amount' => 1500,
            'status' => 'pending',
        ]);
    }

    public function test_la_api_movil_rechaza_montos_invalidos(): void
    {
        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/mobile/v1/driver/wallet/withdraw', ['amount' => 500])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEqualsWithDelta(5000.0, $this->balance(), 0.001);
    }

    public function test_un_cliente_no_puede_usar_la_billetera_del_repartidor(): void
    {
        $clientType = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $client = User::factory()->create(['type_user_id' => $clientType->id]);

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/driver/wallet/withdrawals')
            ->assertStatus(403);
    }

    // ────────────────────────────────────────────────────────────
    // Aprobacion manual desde el panel del admin
    // ────────────────────────────────────────────────────────────

    public function test_el_admin_aprueba_y_rechaza_desde_el_panel(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);

        $this->actingAs($this->admin)
            ->getJson('/admin/withdrawals')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('withdrawals.0.id', $withdrawal->id)
            ->assertJsonPath('withdrawals.0.amount', 1500);

        $this->actingAs($this->admin)
            ->postJson('/admin/withdrawals/'.$withdrawal->id.'/approve')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('withdrawal.status', 'approved');

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_APPROVED,
            $withdrawal->fresh()->status
        );

        $second = $this->service->requestWithdrawal($this->driver, 1000);

        $this->actingAs($this->admin)
            ->postJson('/admin/withdrawals/'.$second->id.'/reject', [
                'reason' => 'No se pudo verificar la cuenta',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('withdrawal.status', 'rejected');

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_REJECTED,
            $second->fresh()->status
        );
    }

    public function test_un_usuario_sin_permiso_no_administra_retiros(): void
    {
        $withdrawal = $this->service->requestWithdrawal($this->driver, 1500);
        $clientType = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $client = User::factory()->create(['type_user_id' => $clientType->id]);

        $this->actingAs($client)
            ->postJson('/admin/withdrawals/'.$withdrawal->id.'/approve')
            ->assertStatus(403);

        $this->assertSame(
            DriverWithdrawalRequest::STATUS_PENDING,
            $withdrawal->fresh()->status
        );
    }
}

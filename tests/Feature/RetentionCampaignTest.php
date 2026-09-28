<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MarketingCampaignRule;
use App\Models\MarketingLog;
use App\Models\Order;
use App\Models\Provider;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\RetentionCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RetentionCampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private User $otherOwner;

    private Provider $otherProvider;

    private User $cliente;

    private User $otroCliente;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);

        $this->owner = User::create([
            'name' => 'Comercio Uno',
            'email' => 'comercio1@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio Uno',
            'phone' => '+5491100000001',
            'is_active' => true,
        ]);

        $this->otherOwner = User::create([
            'name' => 'Comercio Dos',
            'email' => 'comercio2@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->otherProvider = Provider::create([
            'user_id' => $this->otherOwner->id,
            'business_name' => 'Comercio Dos',
            'phone' => '+5491100000002',
            'is_active' => true,
        ]);

        $this->cliente = $this->crearCliente('Cliente Uno', 'cliente1@example.com', '+5491111111111');
        $this->otroCliente = $this->crearCliente('Cliente Dos', 'cliente2@example.com', '+5491122222222');
    }

    public function test_envia_mensaje_a_cliente_que_cumple_los_dias_configurados(): void
    {
        $regla = $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $resumen['rules']);
        $this->assertSame(1, $resumen['sent']);
        $this->assertSame(0, $resumen['failed']);

        $this->assertDatabaseHas('marketing_logs', [
            'provider_id' => $this->provider->id,
            'user_id' => $this->cliente->id,
            'campaign_rule_id' => $regla->id,
            'status' => MarketingLog::STATUS_SENT,
        ]);
    }

    public function test_personaliza_el_mensaje_con_nombre_dias_comercio_y_cupon(): void
    {
        config()->set('marketing.driver', 'webhook');
        config()->set('marketing.webhook.url', 'https://gateway.example.com/send');
        Http::fake(['gateway.example.com/*' => Http::response(['ok' => true], 200)]);

        $this->regla([
            'days_inactive' => 20,
            'discount_code' => 'VUELVE20',
            'message_template' => 'Hola {nombre}, hace {dias} dias que no pedes en {comercio}. Cupon {cupon}',
        ]);

        $this->pedido($this->provider, $this->cliente, now()->subDays(20));

        $this->artisan('marketing:run-retention')->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://gateway.example.com/send'
                && $request['to'] === '+5491111111111'
                && $request['message'] === 'Hola Cliente Uno, hace 20 dias que no pedes en Comercio Uno. Cupon VUELVE20'
                && $request['campaign']['rule_type'] === MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER
                && $request['provider']['business_name'] === 'Comercio Uno';
        });
    }

    public function test_webhook_rechazado_deja_el_log_en_failed_y_permite_reintento(): void
    {
        config()->set('marketing.driver', 'webhook');
        config()->set('marketing.webhook.url', 'https://gateway.example.com/send');
        Http::fake([
            'gateway.example.com/*' => Http::sequence()
                ->push(['error' => 'sin saldo'], 500)
                ->push(['ok' => true], 200),
        ]);

        $this->regla(['days_inactive' => 10]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(10));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertSame(1, $resumen['failed']);
        $this->assertDatabaseHas('marketing_logs', [
            'user_id' => $this->cliente->id,
            'status' => MarketingLog::STATUS_FAILED,
        ]);

        $segunda = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $segunda['sent']);
        $this->assertDatabaseHas('marketing_logs', [
            'user_id' => $this->cliente->id,
            'status' => MarketingLog::STATUS_SENT,
        ]);
    }

    public function test_webhook_sin_url_no_marca_el_log_como_enviado(): void
    {
        config()->set('marketing.driver', 'webhook');
        config()->set('marketing.webhook.url', null);

        $this->regla(['days_inactive' => 10]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(10));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $resumen['failed']);
        $this->assertDatabaseHas('marketing_logs', [
            'user_id' => $this->cliente->id,
            'status' => MarketingLog::STATUS_FAILED,
        ]);
    }

    public function test_no_reenvia_si_ya_hubo_envio_hoy(): void
    {
        $regla = $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $primera = app(RetentionCampaignService::class)->run(now());
        $segunda = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $primera['sent']);
        $this->assertSame(0, $segunda['sent']);
        $this->assertSame(1, $segunda['skipped_duplicate']);
        $this->assertSame(1, MarketingLog::where('user_id', $this->cliente->id)->count());
    }

    public function test_no_reenvia_a_quien_ya_convio_hoy(): void
    {
        $regla = $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        MarketingLog::create([
            'provider_id' => $this->provider->id,
            'user_id' => $this->cliente->id,
            'campaign_rule_id' => $regla->id,
            'sent_at' => now(),
            'status' => MarketingLog::STATUS_CONVERTED,
        ]);

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertSame(1, $resumen['skipped_duplicate']);
    }

    public function test_reenvia_si_el_envio_anterior_fue_de_otro_dia(): void
    {
        $regla = $this->regla(['days_inactive' => 15]);

        MarketingLog::create([
            'provider_id' => $this->provider->id,
            'user_id' => $this->cliente->id,
            'campaign_rule_id' => $regla->id,
            'sent_at' => now()->subDays(1),
            'status' => MarketingLog::STATUS_SENT,
        ]);

        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $resumen['sent']);
    }

    public function test_solo_envia_a_quien_tiene_telono(): void
    {
        $sinTelefono = $this->crearCliente('Cliente Sin Tel', 'sin-tel@example.com', null);
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $sinTelefono, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertSame(1, $resumen['skipped_no_phone']);
        $this->assertDatabaseMissing('marketing_logs', ['user_id' => $sinTelefono->id]);
    }

    public function test_no_envia_a_clientes_de_otros_comercios(): void
    {
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->otherProvider, $this->cliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertDatabaseMissing('marketing_logs', ['user_id' => $this->cliente->id]);
    }

    public function test_ignora_la_regla_deshabilitada(): void
    {
        $this->regla(['days_inactive' => 15, 'is_enabled' => false]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['rules']);
        $this->assertSame(0, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 0);
    }

    public function test_ignora_reglas_de_otros_tipos(): void
    {
        MarketingCampaignRule::create([
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_WELCOME_BACK,
            'days_inactive' => 15,
            'message_template' => 'Hola {nombre}',
            'is_enabled' => true,
        ]);

        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['rules']);
        $this->assertSame(0, $resumen['sent']);
    }

    public function test_sin_reglas_no_hace_nada(): void
    {
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['rules']);
        $this->assertSame(0, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 0);
    }

    public function test_respeta_los_dias_de_umbral_exactos(): void
    {
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(14));
        $this->pedido($this->provider, $this->otroCliente, now()->subDays(16));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 0);
    }

    public function test_usa_el_pedido_mas_reciente_como_ultima_compra(): void
    {
        $this->regla(['days_inactive' => 30]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(120));
        $this->pedido($this->provider, $this->cliente, now()->subDays(30));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $resumen['sent']);
    }

    public function test_usa_el_pedido_mas_reciente_que_no_este_cancelado(): void
    {
        $this->regla(['days_inactive' => 30]);

        $this->pedido($this->provider, $this->cliente, now()->subDays(90));
        $cancelado = $this->pedido($this->provider, $this->cliente, now()->subDays(30));
        $cancelado->update(['status' => Order::STATUS_CANCELLED]);

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 0);
    }

    public function test_avisa_el_cliente_que_ya_paso_la_ventana(): void
    {
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(40));

        Log::spy();

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(0, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 0);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $mensaje) => str_contains($mensaje, 'paso la ventana'));
    }

    public function test_tolerancia_de_ventana_recupera_clientes_si_fallo_el_cron(): void
    {
        config()->set('marketing.inactive_grace_days', 3);
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(17));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $resumen['sent']);
    }

    public function test_procesa_varios_clientes_del_mismo_comercio(): void
    {
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));
        $this->pedido($this->provider, $this->otroCliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(2, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 2);
    }

    public function test_respeta_el_tope_de_envios_por_comercio(): void
    {
        config()->set('marketing.max_sends_per_provider', 1);
        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(15));
        $this->pedido($this->provider, $this->otroCliente, now()->subDays(15));

        $resumen = app(RetentionCampaignService::class)->run(now());

        $this->assertSame(1, $resumen['sent']);
        $this->assertDatabaseCount('marketing_logs', 1);
    }

    public function test_comando_acepta_fecha_de_referencia(): void
    {
        config()->set('marketing.driver', 'webhook');
        config()->set('marketing.webhook.url', 'https://gateway.example.com/send');
        Http::fake(['gateway.example.com/*' => Http::response(['ok' => true], 200)]);

        $this->regla(['days_inactive' => 15]);
        $this->pedido($this->provider, $this->cliente, now()->subDays(20));

        $this->artisan('marketing:run-retention', ['--date' => now()->subDays(5)->toDateString()])
            ->assertSuccessful();

        $this->assertDatabaseCount('marketing_logs', 1);
    }

    public function test_comando_sin_reglas_termina_correctamente(): void
    {
        $this->artisan('marketing:run-retention')
            ->expectsOutputToContain('No hay reglas de retencion habilitadas')
            ->assertSuccessful();
    }

    public function test_mensaje_queda_registrado_en_el_log_por_defecto(): void
    {
        $this->regla([
            'days_inactive' => 15,
            'discount_code' => 'VUELVE15',
            'message_template' => 'Hola {nombre}, hace {dias} dias que no pides en {comercio}. Cupon {cupon}',
        ]);

        $this->pedido($this->provider, $this->cliente, now()->subDays(15));

        $this->artisan('marketing:run-retention')->assertSuccessful();

        $this->assertDatabaseCount('marketing_logs', 1);
        $this->assertDatabaseHas('marketing_logs', [
            'user_id' => $this->cliente->id,
            'status' => MarketingLog::STATUS_SENT,
        ]);
    }

    private function crearCliente(string $nombre, string $email, ?string $telefono): User
    {
        return User::create([
            'name' => $nombre,
            'email' => $email,
            'phone' => $telefono,
            'password' => 'password',
        ]);
    }

    private function regla(array $atributos): MarketingCampaignRule
    {
        return MarketingCampaignRule::create($atributos + [
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'message_template' => 'Hola {nombre}, te extrañamos en {comercio}',
            'is_enabled' => true,
        ]);
    }

    private function pedido(Provider $provider, User $cliente, $fecha): Order
    {
        $categoria = Category::firstOrCreate(
            ['provider_id' => $provider->id, 'name' => 'General']
        );

        $order = new Order([
            'user_id' => $cliente->id,
            'provider_id' => $provider->id,
            'order_number' => 'RET-'.uniqid(),
            'status' => Order::STATUS_DELIVERED,
            'payment_method' => 'efectivo',
            'subtotal' => 1000,
            'discount' => 0,
            'delivery_fee' => 0,
            'total' => 1000,
        ]);

        $order->created_at = $fecha;
        $order->updated_at = $fecha;
        $order->save();

        return $order;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Charge;
use App\Models\Country;
use App\Models\FeeRule;
use App\Models\MpSellerAccount;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Settlement;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_guarda_tokens_cifrados(): void
    {
        Config::set('services.mercadopago.app_id', 'TEST-APP');
        Config::set('services.mercadopago.client_secret', 'TEST-SECRET');
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'SELLER-TOKEN',
                'refresh_token' => 'SELLER-REFRESH',
                'user_id' => 999888,
                'public_key' => 'APP-PUB',
                'live_mode' => true,
                'expires_in' => 15552000,
            ], 200),
        ]);

        $seller = $this->makeUser('Comercio');
        $state = $this->connectState($seller->id);

        $this->get('/billetera/mercadopago/callback?code=AUTHCODE&state='.urlencode($state))
            ->assertOk()
            ->assertSee('Cuenta conectada', false);

        $account = MpSellerAccount::where('user_id', $seller->id)->first();
        $this->assertNotNull($account);
        $this->assertSame('999888', $account->mp_user_id);
        $this->assertSame('SELLER-TOKEN', $account->access_token);
        // En BD viajan cifrados, nunca en claro.
        $this->assertNotSame('SELLER-TOKEN', DB::table('mp_seller_accounts')->where('user_id', $seller->id)->value('access_token'));
    }

    public function test_callback_rechaza_state_invalido(): void
    {
        Config::set('services.mercadopago.app_id', 'TEST-APP');
        Config::set('services.mercadopago.client_secret', 'TEST-SECRET');

        $this->get('/billetera/mercadopago/callback?code=AUTHCODE&state=falsificado')
            ->assertStatus(422);

        $this->assertSame(0, MpSellerAccount::count());
    }

    public function test_checkout_usa_token_del_vendedor_y_fee_como_application_fee(): void
    {
        Config::set('services.mercadopago.access_token', 'APP-TOKEN');
        Config::set('services.mercadopago.app_id', 'TEST-APP');
        Config::set('services.mercadopago.client_secret', 'TEST-SECRET');
        FeeRule::create([
            'module' => 'mercado', 'country' => 'AR', 'currency' => 'ARS',
            'percent' => 1, 'fixed_fee' => 0, 'min_fee' => 0, 'active' => true,
        ]);

        [$client, $seller, $order] = $this->makeMarketOrder(10000);
        MpSellerAccount::create([
            'user_id' => $seller->id, 'mp_user_id' => '111',
            'access_token' => 'SELLER-TOKEN', 'refresh_token' => 'R',
            'live_mode' => true,
        ]);

        Http::fake([
            '*/checkout/preferences' => Http::response(['id' => 'PREF-1', 'init_point' => 'https://mp/init'], 201),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/checkout/mercadopago/start', [
                'order_type' => 'order', 'order_id' => $order->id,
            ]);

        $response->assertOk();
        $this->assertTrue((bool) $response->json('split'));

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/checkout/preferences')
                && $request->header('Authorization')[0] === 'Bearer SELLER-TOKEN'
                && abs(((float) $request->data()['application_fee']) - 100.0) < 0.01;
        });

        $this->assertTrue((bool) Charge::where('order_type', 'order')->where('order_id', $order->id)->value('split_marketplace'));
    }

    public function test_checkout_sin_vendedor_conectado_recauda_pideaca(): void
    {
        Config::set('services.mercadopago.access_token', 'APP-TOKEN');
        FeeRule::create([
            'module' => 'mercado', 'country' => 'AR', 'currency' => 'ARS',
            'percent' => 1, 'fixed_fee' => 0, 'min_fee' => 0, 'active' => true,
        ]);

        [$client, $seller, $order] = $this->makeMarketOrder(10000);

        Http::fake([
            '*/checkout/preferences' => Http::response(['id' => 'PREF-9', 'init_point' => 'https://mp/init'], 201),
        ]);

        $response = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/checkout/mercadopago/start', [
                'order_type' => 'order', 'order_id' => $order->id,
            ]);

        $response->assertOk();
        $this->assertFalse((bool) $response->json('split'));

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/checkout/preferences')
                && $request->header('Authorization')[0] === 'Bearer APP-TOKEN'
                && ! array_key_exists('application_fee', $request->data());
        });
    }

    public function test_webhook_con_split_crea_liquidacion_pagada_sola(): void
    {
        Config::set('services.mercadopago.access_token', 'APP-TOKEN');
        Config::set('services.mercadopago.webhook_secret', 'WH-SECRET');
        Config::set('services.mercadopago.app_id', 'TEST-APP');
        Config::set('services.mercadopago.client_secret', 'TEST-SECRET');
        FeeRule::create([
            'module' => 'mercado', 'country' => 'AR', 'currency' => 'ARS',
            'percent' => 1, 'fixed_fee' => 0, 'min_fee' => 0, 'active' => true,
        ]);

        [$client, $seller, $order] = $this->makeMarketOrder(20000);
        MpSellerAccount::create([
            'user_id' => $seller->id, 'mp_user_id' => '111',
            'access_token' => 'SELLER-TOKEN', 'live_mode' => true,
        ]);

        Http::fake([
            '*/checkout/preferences' => Http::response(['id' => 'PREF-5', 'init_point' => 'https://mp/init'], 201),
        ]);

        // Inicia el checkout para generar el charge con split.
        $start = $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/checkout/mercadopago/start', [
                'order_type' => 'order', 'order_id' => $order->id,
            ]);
        $start->assertOk();
        $chargeId = (int) $start->json('charge_id');
        $this->assertGreaterThan(0, $chargeId);

        // Re-fakea el pago con el external_reference real.
        Http::fake([
            '*/v1/payments/PAY-1' => Http::response([
                'id' => 'PAY-1', 'status' => 'approved', 'external_reference' => 'charge:'.$chargeId,
            ], 200),
        ]);

        $requestId = 'req-'.uniqid();
        $ts = (string) time();
        $manifest = "id:PAY-1;request-id:{$requestId};ts:{$ts};";
        $sig = hash_hmac('sha256', $manifest, 'WH-SECRET');

        $resp = $this->postJson('/webhooks/mercadopago', ['type' => 'payment', 'data' => ['id' => 'PAY-1']], [
            'x-signature' => "ts={$ts},v1={$sig}",
            'x-request-id' => $requestId,
        ]);
        $resp->assertOk()->assertJsonPath('status', 'verified');

        $settlement = Settlement::where('user_id', $seller->id)->where('status', 'paid')->first();
        $this->assertNotNull($settlement);
        $this->assertSame('MP:PAY-1', $settlement->payout_reference);
        $this->assertSame('verified', Charge::find($chargeId)->status);
    }

    public function test_seller_status_y_disconnect(): void
    {
        $seller = $this->makeUser('Comercio');

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/mobile/v1/checkout/mercadopago/seller-status')
            ->assertOk()
            ->assertJsonPath('data.connected', false);

        MpSellerAccount::create([
            'user_id' => $seller->id, 'mp_user_id' => '111',
            'access_token' => 'T', 'live_mode' => true,
        ]);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/mobile/v1/checkout/mercadopago/seller-status')
            ->assertOk()
            ->assertJsonPath('data.connected', true);

        $this->actingAs($seller, 'sanctum')
            ->deleteJson('/api/mobile/v1/checkout/mercadopago/disconnect')
            ->assertOk();

        $this->assertSame(0, MpSellerAccount::where('user_id', $seller->id)->count());
    }

    private function connectState(int $userId): string
    {
        $payload = $userId.':'.time().':'.bin2hex(random_bytes(8));
        $sig = hash_hmac('sha256', $payload, (string) config('app.key'));

        return rtrim(strtr(base64_encode($payload.':'.$sig), '+/', '-_'), '=');
    }

    /**
     * @return array{User, User, Order}
     */
    private function makeMarketOrder(float $total): array
    {
        $clientType = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $sellerType = TypeUser::firstOrCreate(['description' => 'Comercio']);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);
        $country = Country::firstOrCreate(['name' => 'Argentina'], ['iso_code' => 'AR']);

        $seller = User::create([
            'name' => 'Comercio Split', 'email' => 'split-'.uniqid().'@example.com',
            'type_user_id' => $sellerType->id, 'user_status_id' => $status->id, 'password' => 'secret123',
        ]);
        $provider = Provider::create([
            'user_id' => $seller->id, 'business_name' => 'Split Store', 'is_active' => true,
        ]);
        $client = User::create([
            'name' => 'Cliente Split', 'email' => 'buysplit-'.uniqid().'@example.com',
            'type_user_id' => $clientType->id, 'user_status_id' => $status->id, 'password' => 'secret123',
        ]);
        $address = Address::create([
            'user_id' => $client->id, 'country_id' => $country->id,
            'street' => 'Calle', 'number' => '1', 'is_primary' => true,
        ]);
        $order = Order::create([
            'provider_id' => $provider->id, 'user_id' => $client->id, 'address_id' => $address->id,
            'order_number' => 'PIDE-'.uniqid(), 'status' => 'pending',
            'subtotal' => $total, 'delivery_fee' => 0, 'discount' => 0, 'total' => $total,
            'payment_method' => 'mercadopago',
        ]);

        return [$client, $seller, $order];
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => $type.' Split',
            'email' => strtolower($type).'-split-'.uniqid().'@example.com',
            'type_user_id' => $typeUser->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }
}

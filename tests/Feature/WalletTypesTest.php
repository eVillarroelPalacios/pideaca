<?php

namespace Tests\Feature;

use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rechaza_invitados(): void
    {
        $this->getJson('/admin/billing/wallet-types')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_rechaza_usuarios_no_admin(): void
    {
        $user = $this->makeUser('Cliente');

        $this->actingAs($user)->getJson('/admin/billing/wallet-types')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lista_tipos(): void
    {
        $admin = $this->makeUser('Admin');
        WalletType::create([
            'country' => 'AR', 'code' => 'testpay', 'name' => 'Test Pay',
            'fields' => ['holder', 'alias'], 'active' => true,
        ]);

        $this->actingAs($admin)->getJson('/admin/billing/wallet-types')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'testpay']);
    }

    public function test_admin_crea_tipo_valido(): void
    {
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/billing/wallet-types', [
            'country' => 'AR',
            'code' => 'TestPay',
            'name' => 'Test Pay',
            'fields' => ['holder', 'alias'],
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('wallet_types', [
            'country' => 'AR',
            'code' => 'testpay',
            'name' => 'Test Pay',
        ]);
        $this->assertSame(['holder', 'alias'], WalletType::firstWhere('code', 'testpay')->fields);
    }

    public function test_rechaza_duplicado_por_pais_con_422(): void
    {
        $admin = $this->makeUser('Admin');
        WalletType::create([
            'country' => 'AR', 'code' => 'testpay', 'name' => 'Test Pay',
            'fields' => ['holder', 'alias'], 'active' => true,
        ]);

        $this->actingAs($admin)->postJson('/admin/billing/wallet-types', [
            'country' => 'AR',
            'code' => 'TESTPAY',
            'name' => 'Duplicado',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(1, WalletType::where('code', 'testpay')->count());
    }

    public function test_rechaza_datos_invalidos_con_422(): void
    {
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin)->postJson('/admin/billing/wallet-types', [
            'country' => 'ARG',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['country', 'code', 'name']);
    }

    public function test_admin_alterna_activo(): void
    {
        $admin = $this->makeUser('Admin');
        $type = WalletType::create([
            'country' => 'ES', 'code' => 'testbizum', 'name' => 'Test Bizum',
            'fields' => ['holder', 'phone'], 'active' => true,
        ]);

        $this->actingAs($admin)->putJson('/admin/billing/wallet-types/'.$type->id, ['active' => false])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse($type->fresh()->active);
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => $type.' Billeteras',
            'email' => strtolower($type).'-'.uniqid().'@example.com',
            'type_user_id' => $typeUser->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }
}

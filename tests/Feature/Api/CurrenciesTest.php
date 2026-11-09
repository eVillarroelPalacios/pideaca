<?php

namespace Tests\Feature\Api;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrenciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/currencies')->assertStatus(401);
        $this->postJson('/api/mobile/v1/currencies', ['code' => 'USD'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/currencies')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_currencies_with_country_default_and_summary(): void
    {
        $admin = $this->makeUser('Admin');
        $argentina = Country::create(['iso_code' => 'AR', 'name' => 'Argentina']);
        $uruguay = Country::create(['iso_code' => 'UY', 'name' => 'Uruguay']);

        Currency::create([
            'country_id' => $argentina->id,
            'code' => 'ARS',
            'name' => 'Peso argentino',
            'symbol' => '$',
            'symbol_position' => 'before',
            'symbol_space' => true,
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'locale' => 'es-AR',
            'is_active' => true,
        ]);

        $usd = Currency::create([
            'country_id' => $uruguay->id,
            'code' => 'USD',
            'name' => 'Dólar estadounidense',
            'symbol' => 'US$',
            'symbol_position' => 'before',
            'symbol_space' => true,
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'locale' => 'en-US',
            'is_active' => false,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/currencies')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'code' => 'ARS',
                'is_default' => true,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $usd->id,
                'code' => 'USD',
                'is_default' => false,
                'can_delete' => true,
            ])
            ->assertJsonFragment(['id' => $uruguay->id, 'name' => 'Uruguay'])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.active', 1)
            ->assertJsonPath('summary.default_code', 'ARS');

        $listed = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/currencies')
            ->json('data'))
            ->firstWhere('code', 'USD');

        $this->assertSame('Uruguay', $listed['country']['name']);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->currenciesPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/currencies')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_currency_with_normalized_code(): void
    {
        $admin = $this->makeUser('Admin');
        $chile = Country::create(['iso_code' => 'CL', 'name' => 'Chile']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/currencies', [
                'country_id' => $chile->id,
                'code' => '  clp  ',
                'name' => 'Peso chileno',
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimals' => 0,
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'locale' => 'es-CL',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('currency.code', 'CLP')
            ->assertJsonPath('currency.symbol_space', true)
            ->assertJsonPath('currency.is_active', true)
            ->assertJsonPath('currency.is_default', false)
            ->assertJsonPath('currency.can_delete', true);
    }

    public function test_duplicate_country_code_and_invalid_fields_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        $argentina = Country::create(['iso_code' => 'AR', 'name' => 'Argentina']);
        Currency::create([
            'country_id' => $argentina->id,
            'code' => 'ARS',
            'name' => 'Peso argentino',
            'symbol' => '$',
            'symbol_position' => 'before',
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'locale' => 'es-AR',
        ]);

        $brasil = Country::create(['iso_code' => 'BR', 'name' => 'Brasil']);

        // Mismo pais: una moneda por pais.
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/currencies', $this->payload($argentina->id, 'XXX'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('country_id');

        // Mismo codigo.
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/currencies', $this->payload($brasil->id, 'ARS'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        // Codigo con longitud invalida.
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/currencies', $this->payload($brasil->id, 'AR'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        // Decimales fuera de rango.
        $payload = $this->payload($brasil->id, 'BRL');
        $payload['decimals'] = 9;
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/currencies', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('decimals');

        // Pais inexistente y posicion invalida.
        $payload = $this->payload(99999, 'ZZZ');
        $payload['symbol_position'] = 'middle';
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/currencies', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['country_id', 'symbol_position']);
    }

    public function test_update_changes_the_currency(): void
    {
        $admin = $this->makeUser('Admin');
        $argentina = Country::create(['iso_code' => 'AR', 'name' => 'Argentina']);
        $currency = Currency::create($this->payload($argentina->id, 'ARA') + ['name' => 'Austral']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/currencies/'.$currency->id, $this->payload($argentina->id, 'ARS'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('currency.code', 'ARS')
            ->assertJsonPath('currency.name', 'Peso argentino');

        $this->assertSame('ARS', $currency->fresh()->code);
    }

    public function test_delete_removes_a_non_default_currency(): void
    {
        $admin = $this->makeUser('Admin');
        $uruguay = Country::create(['iso_code' => 'UY', 'name' => 'Uruguay']);
        $currency = Currency::create($this->payload($uruguay->id, 'USD'));

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/currencies/'.$currency->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Moneda eliminada correctamente.');

        $this->assertNull(Currency::find($currency->id));
    }

    public function test_the_default_currency_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $argentina = Country::create(['iso_code' => 'AR', 'name' => 'Argentina']);
        $currency = Currency::create($this->payload($argentina->id, 'ARS'));

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/currencies/'.$currency->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CURRENCY_IS_DEFAULT')
            ->assertJsonPath('message', 'No se puede eliminar la moneda por defecto de la plataforma.');

        $this->assertNotNull(Currency::find($currency->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $countryId, string $code): array
    {
        return [
            'country_id' => $countryId,
            'code' => $code,
            'name' => 'Peso argentino',
            'symbol' => '$',
            'symbol_position' => 'before',
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'locale' => 'es-AR',
        ];
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Monedas',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function currenciesPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'tipo-monedas'],
            ['description' => 'Tipo de monedas', 'module_id' => $module->id],
        );
    }
}

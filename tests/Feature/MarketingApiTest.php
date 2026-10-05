<?php

namespace Tests\Feature;

use App\Models\MarketingCampaignRule;
use App\Models\MarketingLog;
use App\Models\Provider;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private User $otherOwner;

    private Provider $otherProvider;

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
            'is_active' => true,
        ]);
    }

    public function test_guest_gets_401_on_marketing_endpoints(): void
    {
        $this->getJson('/api/v1/provider/marketing/rules')->assertStatus(401);
        $this->putJson('/api/v1/provider/marketing/rules', [])->assertStatus(401);
    }

    public function test_customer_gets_403_on_marketing_endpoints(): void
    {
        $cliente = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($cliente)
            ->getJson('/api/v1/provider/marketing/rules')
            ->assertStatus(403);

        $this->actingAs($cliente)
            ->putJson('/api/v1/provider/marketing/rules', [])
            ->assertStatus(403);
    }

    public function test_index_returns_empty_state_with_catalog(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/marketing/rules')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('rules', [])
            ->assertJsonPath('summary.total', 0)
            ->assertJsonPath('summary.enabled', 0)
            ->assertJsonCount(3, 'types')
            ->assertJsonStructure(['default_templates' => ['INACTIVE_CUSTOMER', 'RECURRING_DAY_REMINDER', 'WELCOME_BACK']]);
    }

    public function test_index_lists_provider_rules_with_state_and_discount(): void
    {
        $regla = MarketingCampaignRule::create([
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 30,
            'message_template' => 'Hola {nombre}, te extraÃ±amos en {comercio}',
            'discount_code' => 'VUELVE30',
            'is_enabled' => true,
        ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/marketing/rules')
            ->assertStatus(200)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.enabled', 1)
            ->assertJsonPath('rules.0.id', $regla->id)
            ->assertJsonPath('rules.0.rule_type', MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER)
            ->assertJsonPath('rules.0.days_inactive', 30)
            ->assertJsonPath('rules.0.discount_code', 'VUELVE30')
            ->assertJsonPath('rules.0.is_enabled', true);
    }

    public function test_index_does_not_leak_rules_of_other_providers(): void
    {
        MarketingCampaignRule::create([
            'provider_id' => $this->otherProvider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 10,
            'message_template' => 'Mensaje ajeno',
            'is_enabled' => true,
        ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/marketing/rules')
            ->assertStatus(200)
            ->assertJsonCount(0, 'rules');
    }

    public function test_update_creates_rule_and_enables_recovery(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 20,
                'message_template' => 'Hola {nombre}, hace {dias} dias que no pides en {comercio}. Cupon {cupon}',
                'discount_code' => 'BIENVENIDO20',
                'is_enabled' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('rule.rule_type', MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER)
            ->assertJsonPath('rule.days_inactive', 20)
            ->assertJsonPath('rule.is_enabled', true);

        $this->assertDatabaseHas('marketing_campaign_rules', [
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 20,
            'discount_code' => 'BIENVENIDO20',
            'is_enabled' => true,
        ]);
    }

    public function test_update_personalizes_message_and_toggles_enabled(): void
    {
        $regla = MarketingCampaignRule::create([
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 15,
            'message_template' => 'Mensaje original',
            'is_enabled' => false,
        ]);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 45,
                'message_template' => 'Hola {nombre}, hace {dias} dias que te guardamos en {comercio}. Cupon {cupon}',
                'discount_code' => '45OFF',
                'is_enabled' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('rule.id', $regla->id)
            ->assertJsonPath('rule.days_inactive', 45)
            ->assertJsonPath('rule.is_enabled', true);

        $this->assertSame(1, MarketingCampaignRule::where('provider_id', $this->provider->id)->count());
        $this->assertDatabaseHas('marketing_campaign_rules', [
            'id' => $regla->id,
            'message_template' => 'Hola {nombre}, hace {dias} dias que te guardamos en {comercio}. Cupon {cupon}',
            'discount_code' => '45OFF',
            'is_enabled' => true,
        ]);
    }

    public function test_update_can_disable_rule_without_deleting_it(): void
    {
        $regla = MarketingCampaignRule::create([
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 15,
            'message_template' => 'Hola {nombre}',
            'is_enabled' => true,
        ]);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 15,
                'message_template' => 'Hola {nombre}, hace {dias} dias que no pides en {comercio}. Cupon {cupon}',
                'is_enabled' => false,
            ])
            ->assertStatus(200)
            ->assertJsonPath('rule.is_enabled', false);

        $this->assertDatabaseHas('marketing_campaign_rules', [
            'id' => $regla->id,
            'is_enabled' => false,
        ]);
    }

    public function test_update_supports_the_other_rule_types(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'rule_type' => MarketingCampaignRule::TYPE_RECURRING_DAY_REMINDER,
                'day_of_week' => 6,
                'message_template' => 'Hola {nombre}, hoy es tu dia en {comercio}. Cupon {cupon}',
                'is_enabled' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('rule.rule_type', MarketingCampaignRule::TYPE_RECURRING_DAY_REMINDER)
            ->assertJsonPath('rule.day_of_week', 6);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'rule_type' => MarketingCampaignRule::TYPE_WELCOME_BACK,
                'message_template' => 'Hola {nombre}, vuelve a {comercio}. Cupon {cupon}',
                'is_enabled' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('rule.rule_type', MarketingCampaignRule::TYPE_WELCOME_BACK);
    }

    public function test_update_validates_payload(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message_template', 'is_enabled']);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 15,
                'message_template' => 'Hola',
                'is_enabled' => true,
                'rule_type' => 'NO_EXISTE',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rule_type']);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 0,
                'message_template' => 'Hola',
                'is_enabled' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['days_inactive']);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'day_of_week' => 9,
                'message_template' => 'Hola',
                'is_enabled' => true,
                'rule_type' => MarketingCampaignRule::TYPE_RECURRING_DAY_REMINDER,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week']);
    }

    public function test_update_rejects_messages_without_the_required_tokens(): void
    {
        $completo = 'Hola {nombre}, hace {dias} dias que no pides en {comercio}. Cupon {cupon}';

        $this->assertSame(
            ['{nombre}', '{dias}', '{comercio}', '{cupon}'],
            MarketingCampaignRule::requiredTokensFor(MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER)
        );

        foreach (MarketingCampaignRule::requiredTokensFor(MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER) as $token) {
            $respuesta = $this->actingAs($this->owner)
                ->putJson('/api/v1/provider/marketing/rules', [
                    'days_inactive' => 20,
                    'message_template' => str_replace($token, '', $completo),
                    'is_enabled' => true,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['message_template']);

            $this->assertStringContainsString($token, (string) $respuesta->json('errors.message_template.0'));
        }

        $this->assertSame(0, MarketingCampaignRule::where('provider_id', $this->provider->id)->count());

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 20,
                'message_template' => $completo,
                'is_enabled' => true,
            ])
            ->assertStatus(201);

        $this->assertSame(1, MarketingCampaignRule::where('provider_id', $this->provider->id)->count());
    }

    public function test_update_does_not_touch_other_providers_rules(): void
    {
        $ajena = MarketingCampaignRule::create([
            'provider_id' => $this->otherProvider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 10,
            'message_template' => 'No tocar',
            'is_enabled' => true,
        ]);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/marketing/rules', [
                'days_inactive' => 20,
                'message_template' => 'Hola {nombre}, hace {dias} dias que no pides en {comercio}. Cupon {cupon}',
                'is_enabled' => true,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('marketing_campaign_rules', [
            'id' => $ajena->id,
            'days_inactive' => 10,
            'message_template' => 'No tocar',
        ]);
    }

    public function test_marketing_logs_are_not_exposed_by_the_api(): void
    {
        $regla = MarketingCampaignRule::create([
            'provider_id' => $this->provider->id,
            'rule_type' => MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER,
            'days_inactive' => 15,
            'message_template' => 'Hola {nombre}',
            'is_enabled' => true,
        ]);

        $cliente = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'phone' => '+5491112345678',
            'password' => 'password',
        ]);

        MarketingLog::create([
            'provider_id' => $this->provider->id,
            'user_id' => $cliente->id,
            'campaign_rule_id' => $regla->id,
            'sent_at' => now(),
            'status' => MarketingLog::STATUS_SENT,
        ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/marketing/rules')
            ->assertStatus(200)
            ->assertJsonMissing(['campaign_rule_id' => $regla->id])
            ->assertJsonMissing(['user_id' => $cliente->id]);
    }
}

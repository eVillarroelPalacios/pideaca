<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceRating;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function fakeExpoOk(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200),
        ]);
    }

    private function fakeExpoDeadToken(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [[
                'status' => 'error',
                'details' => ['error' => 'DeviceNotRegistered'],
            ]]], 200),
        ]);
    }

    public function test_push_tokens_and_notifications_require_a_token(): void
    {
        $this->postJson('/api/mobile/v1/push-tokens')->assertStatus(401);
        $this->getJson('/api/mobile/v1/notifications')->assertStatus(401);
    }

    public function test_store_and_remove_a_push_token(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/push-tokens', [
                'token' => 'ExponentPushToken[abc123]',
                'platform' => 'android',
            ])
            ->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $client->id,
            'token' => 'ExponentPushToken[abc123]',
        ]);

        $this->actingAs($client, 'sanctum')
            ->deleteJson('/api/mobile/v1/push-tokens', ['token' => 'ExponentPushToken[abc123]'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_tokens', ['token' => 'ExponentPushToken[abc123]']);
    }

    public function test_store_rejects_an_invalid_token(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/push-tokens', ['token' => 'fcm:xyz'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'INVALID_PUSH_TOKEN']);
    }

    public function test_a_token_can_move_between_users(): void
    {
        $first = $this->makeClient('uno@example.com');
        $second = $this->makeClient('dos@example.com');

        $this->actingAs($first, 'sanctum')
            ->postJson('/api/mobile/v1/push-tokens', ['token' => 'ExponentPushToken[mismo]'])
            ->assertCreated();

        $this->actingAs($second, 'sanctum')
            ->postJson('/api/mobile/v1/push-tokens', ['token' => 'ExponentPushToken[mismo]'])
            ->assertCreated();

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $second->id,
            'token' => 'ExponentPushToken[mismo]',
        ]);
        $this->assertDatabaseMissing('push_tokens', ['user_id' => $first->id]);
    }

    public function test_notifications_list_shows_only_mine_with_unread_count(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient('otro@example.com');

        UserNotification::create(['user_id' => $client->id, 'type' => 'a', 'title' => 'Uno']);
        UserNotification::create(['user_id' => $client->id, 'type' => 'b', 'title' => 'Dos', 'read_at' => now()]);
        UserNotification::create(['user_id' => $other->id, 'type' => 'c', 'title' => 'Ajena']);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/notifications')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.unread_count', 1);

        $this->assertSame('Dos', $response->json('data.0.title'));

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/notifications?unread_only=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Uno');
    }

    public function test_mark_as_read_and_read_all(): void
    {
        $client = $this->makeClient();
        $other = $this->makeClient('otro@example.com');

        $mine = UserNotification::create(['user_id' => $client->id, 'type' => 'a', 'title' => 'Mía']);
        $foreign = UserNotification::create(['user_id' => $other->id, 'type' => 'b', 'title' => 'Ajena']);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/notifications/'.$foreign->id.'/read')
            ->assertStatus(404);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/notifications/'.$mine->id.'/read')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.title', 'Mía');

        $this->assertNotNull($mine->fresh()->read_at);

        UserNotification::create(['user_id' => $client->id, 'type' => 'c', 'title' => 'Otra']);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 1);
    }

    public function test_accepting_a_quote_notifies_the_provider_and_pushes(): void
    {
        $this->fakeExpoOk();
        $client = $this->makeClient('cliente@example.com');
        $owner = $this->makeProviderOwner('dueno@example.com');
        $provider = $this->makeProvider($owner);
        $client->pushTokens()->create(['token' => 'ExponentPushToken[cli]', 'platform' => 'android']);
        $owner->pushTokens()->create(['token' => 'ExponentPushToken[prov]', 'platform' => 'android']);

        $order = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE, ServiceOrder::STATUS_QUOTED);
        $proposal = ServiceQuote::create([
            'service_order_id' => $order->id,
            'provider_id' => $provider->id,
            'proposed_price' => 15000,
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/accept-quote', [
                'quote' => $proposal->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $owner->id,
            'type' => 'order_confirmed',
        ]);

        Http::assertSent(function ($request) {
            return str_contains((string) $request->url(), 'exp.host')
                && str_contains((string) $request->body(), 'ExponentPushToken[prov]');
        });
    }

    public function test_a_new_proposal_notifies_the_customer(): void
    {
        $this->fakeExpoOk();
        $client = $this->makeClient();
        $owner = $this->makeProviderOwner('dueno@example.com');
        $provider = $this->makeProvider($owner);
        $this->makeServiceGroup($provider);

        $order = $this->makeOrder($client, ServiceOrder::TYPE_QUOTE);

        ServiceQuote::create([
            'service_order_id' => $order->id,
            'provider_id' => $provider->id,
            'proposed_price' => 9000,
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $client->id,
            'type' => 'quote_proposal',
        ]);
    }

    public function test_a_rating_notifies_the_rated_user(): void
    {
        $this->fakeExpoOk();
        $client = $this->makeClient();
        $owner = $this->makeProviderOwner('dueno@example.com');
        $provider = $this->makeProvider($owner);
        $this->makeServiceGroup($provider);

        $order = $this->makeOrder($client, ServiceOrder::TYPE_SCHEDULED, ServiceOrder::STATUS_COMPLETED);
        $order->merchant_id = $provider->id;
        $order->save();

        $this->actingAs($client, 'sanctum')
            ->postJson('/api/mobile/v1/service-orders/'.$order->id.'/rate', ['stars' => 5])
            ->assertOk();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $owner->id,
            'type' => 'rating_received',
        ]);
    }

    public function test_expired_tokens_are_pruned(): void
    {
        $this->fakeExpoDeadToken();

        $client = $this->makeClient();
        $client->pushTokens()->create(['token' => 'ExponentPushToken[viejo]']);

        $order = $this->makeOrder($client, ServiceOrder::TYPE_EMERGENCY);
        $order->status = ServiceOrder::STATUS_PROVIDER_DISPATCHED;
        $order->save();

        $this->assertDatabaseMissing('push_tokens', ['token' => 'ExponentPushToken[viejo]']);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $client->id,
            'type' => 'order_dispatched',
        ]);
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

    private function makeProviderOwner(string $email): User
    {
        $type = TypeUser::firstOrCreate(['description' => 'Prestador']);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => 'Dueño '.$email,
            'email' => $email,
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function makeProvider(User $owner): Provider
    {
        return Provider::create([
            'user_id' => $owner->id,
            'business_name' => 'Prestador de '.$owner->email,
            'is_active' => true,
            'is_verified' => true,
        ]);
    }

    private function makeServiceGroup(?Provider $provider = null): SubGroup
    {
        $group = Group::create(['description' => 'Servicios del hogar']);
        config(['catalog.service_group_id' => $group->id]);

        $subgroup = SubGroup::create(['description' => 'Plomería', 'group_id' => $group->id]);

        if ($provider) {
            $provider->subgroups()->attach($subgroup->id);
            $provider->serviceTypes()->attach(
                ServiceType::firstOrCreate(['code' => 'EMERGENCY'], ['name' => 'Urgencia'])->id
            );
        }

        return $subgroup;
    }

    private function makeOrder(User $client, string $type, string $status = ServiceOrder::STATUS_REQUESTED): ServiceOrder
    {
        $subgroup = SubGroup::first() ?? $this->makeServiceGroup();

        return ServiceOrder::create([
            'customer_id' => $client->id,
            'sub_group_id' => $subgroup->id,
            'order_type' => $type,
            'status' => $status,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
            'description' => 'Orden de prueba',
        ]);
    }
}

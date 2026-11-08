<?php

namespace Tests\Feature\Api;

use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_does_not_require_authentication(): void
    {
        $this->getJson('/api/mobile/v1/ping')
            ->assertOk()
            ->assertJson(['success' => true, 'version' => 'v1']);
    }

    public function test_login_returns_a_bearer_token_and_the_user(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'CLIENTE@Example.com ',
            'password' => 'secret123',
            'device_name' => 'Pixel 7',
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'token_type' => 'Bearer',
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'Cliente');

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 7']);
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $this->makeUser();

        $this->postJson('/api/mobile/v1/auth/login', [
            'email' => 'cliente@example.com',
            'password' => 'incorrecta',
        ])->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'INVALID_CREDENTIALS']);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/auth/me')->assertStatus(401);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'cliente@example.com');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/v1/auth/logout')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // El guard resuelto se cachea entre requests del mismo test.
        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_register_creates_the_user_and_returns_a_token(): void
    {
        $type = TypeUser::create(['description' => 'Cliente']);
        UserStatus::create(['status' => 'Activo']);
        config(['catalog.registrable_type_ids' => [$type->id]]);

        $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Cliente Nuevo',
            'email' => 'Nuevo@Example.com',
            'type_user_id' => $type->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated()
            ->assertJson([
                'success' => true,
                'token_type' => 'Bearer',
            ])
            ->assertJsonPath('user.email', 'nuevo@example.com')
            ->assertJsonPath('user.role', 'Cliente');

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
            'type_user_id' => $type->id,
        ]);
    }

    public function test_register_rejects_a_duplicate_email(): void
    {
        $this->makeUser();

        $type = TypeUser::firstOrCreate(['description' => 'Cliente']);
        config(['catalog.registrable_type_ids' => [$type->id]]);

        $this->postJson('/api/mobile/v1/auth/register', [
            'name' => 'Otro Cliente',
            'email' => 'cliente@example.com',
            'type_user_id' => $type->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'EMAIL_TAKEN']);
    }

    private function makeUser(): User
    {
        $type = TypeUser::create(['description' => 'Cliente']);
        $status = UserStatus::create(['status' => 'Activo']);

        return User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.com',
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function loginToken(User $user): string
    {
        return $this->postJson('/api/mobile/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->json('token');
    }
}

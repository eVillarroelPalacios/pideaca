<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypeUserPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/type-users/pages-data')->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/type-users/pages-data')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_pages_data_returns_types_with_assigned_pages_and_catalog(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Prestador']);
        $page = $this->makePage('usuarios');

        $type->assignedPages()->sync([$page->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/type-users/pages-data')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $type->id,
                'description' => 'Prestador',
                'users_count' => 0,
                'assigned_page_ids' => [$page->id],
            ])
            ->assertJsonFragment([
                'id' => $page->id,
                'description' => 'Usuarios',
                'url' => 'usuarios',
            ]);
    }

    public function test_update_pages_syncs_the_template_and_cascades_to_users(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Prestador']);
        $user = $this->makeUserOfType($type, 'Prestador Uno');
        $pageA = $this->makePage('usuarios');
        $pageB = $this->makePage('grupos');

        $type->assignedPages()->sync([$pageA->id]);
        $user->pages()->sync([$pageA->id]);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/type-users/'.$type->id.'/pages', [
                'page_ids' => [$pageB->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('users_count', 1);

        $this->assertSame([$pageB->id], $type->fresh()->assignedPages()->pluck('pages.id')->all());
        $this->assertSame([$pageB->id], $user->fresh()->pages()->pluck('pages.id')->all());
    }

    public function test_update_pages_rejects_unknown_pages(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Prestador']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/type-users/'.$type->id.'/pages', [
                'page_ids' => [99999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('page_ids.0');
    }

    public function test_bulk_attach_and_detach_cascade_to_users(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Prestador']);
        $user = $this->makeUserOfType($type, 'Prestador Uno');
        $page = $this->makePage('usuarios');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/type-users/'.$type->id.'/pages/bulk', [
                'page_ids' => [$page->id],
                'action' => 'attach',
            ])
            ->assertOk()
            ->assertJsonPath('action', 'attach')
            ->assertJsonPath('count', 1);

        $this->assertTrue($type->fresh()->assignedPages()->where('pages.id', $page->id)->exists());
        $this->assertTrue($user->fresh()->pages()->where('pages.id', $page->id)->exists());

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/type-users/'.$type->id.'/pages/bulk', [
                'page_ids' => [$page->id],
                'action' => 'detach',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Páginas quitadas.');

        $this->assertFalse($type->fresh()->assignedPages()->where('pages.id', $page->id)->exists());
        $this->assertFalse($user->fresh()->pages()->where('pages.id', $page->id)->exists());
    }

    public function test_single_attach_and_detach(): void
    {
        $admin = $this->makeUser('Admin');
        $type = TypeUser::create(['description' => 'Prestador']);
        $user = $this->makeUserOfType($type, 'Prestador Uno');
        $page = $this->makePage('usuarios');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/type-users/'.$type->id.'/pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('page_id', $page->id);

        $this->assertTrue($user->fresh()->pages()->where('pages.id', $page->id)->exists());

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/type-users/'.$type->id.'/pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('message', 'Página quitada.');

        $this->assertFalse($type->fresh()->assignedPages()->where('pages.id', $page->id)->exists());
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Paginas',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function makeUserOfType(TypeUser $type, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'-'.uniqid('', true).'@example.com',
            'type_user_id' => $type->id,
            'password' => 'secret123',
        ]);
    }

    private function makePage(string $url): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => $url],
            ['description' => ucfirst($url), 'module_id' => $module->id],
        );
    }
}

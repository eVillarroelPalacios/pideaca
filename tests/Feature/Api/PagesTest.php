<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/pages')->assertStatus(401);
        $this->postJson('/api/mobile/v1/pages', ['description' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/pages')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_pages_with_modules_users_and_summary(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Administrar']);

        $used = Page::create([
            'description' => 'Página en uso',
            'url' => 'en-uso',
            'module_id' => $module->id,
        ]);
        $used->users()->attach($admin->id);

        $free = Page::create([
            'description' => 'Página libre',
            'url' => null,
            'module_id' => $module->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/pages')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $used->id,
                'description' => 'Página en uso',
                'url' => 'en-uso',
                'module_id' => $module->id,
                'users_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $free->id,
                'description' => 'Página libre',
                'url' => null,
                'module_id' => $module->id,
                'users_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonFragment(['id' => $module->id, 'description' => 'Administrar'])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.in_use', 1);

        $listed = collect($this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/pages')
            ->json('data'))
            ->firstWhere('id', $used->id);

        $this->assertSame('Administrar', $listed['module']['description']);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->pagesPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/pages')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_page(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Administrar']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/pages', [
                'description' => '  Nueva página  ',
                'url' => '  nueva-pagina  ',
                'module_id' => $module->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('page.description', 'Nueva página')
            ->assertJsonPath('page.url', 'nueva-pagina')
            ->assertJsonPath('page.module_id', $module->id)
            ->assertJsonPath('page.users_count', 0)
            ->assertJsonPath('page.can_delete', true);
    }

    public function test_store_normalizes_an_empty_url_to_null(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Administrar']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/pages', [
                'description' => 'Página sin URL',
                'url' => '   ',
                'module_id' => $module->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('page.url', null);
    }

    public function test_duplicate_empty_and_unknown_module_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Administrar']);
        Page::create(['description' => 'Páginas', 'module_id' => $module->id]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/pages', [
                'description' => 'Páginas',
                'module_id' => $module->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/pages', [
                'description' => '   ',
                'module_id' => $module->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/pages', [
                'description' => 'Otra',
                'module_id' => 99999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('module_id');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/pages', ['description' => 'Sin módulo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('module_id');
    }

    public function test_update_changes_the_page(): void
    {
        $admin = $this->makeUser('Admin');
        $moduleA = Module::create(['description' => 'Módulo A']);
        $moduleB = Module::create(['description' => 'Módulo B']);
        $page = Page::create(['description' => 'Página A', 'module_id' => $moduleA->id]);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/pages/'.$page->id, [
                'description' => 'Página A Editada',
                'url' => 'editada',
                'module_id' => $moduleB->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('page.description', 'Página A Editada')
            ->assertJsonPath('page.url', 'editada')
            ->assertJsonPath('page.module_id', $moduleB->id);

        $this->assertSame('Página A Editada', $page->fresh()->description);
    }

    public function test_delete_removes_a_page_without_users(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Administrar']);
        $page = Page::create(['description' => 'Página temporal', 'module_id' => $module->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Página eliminada correctamente.');

        $this->assertNull(Page::find($page->id));
    }

    public function test_a_page_with_users_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Administrar']);
        $page = Page::create(['description' => 'Página en uso', 'module_id' => $module->id]);
        $page->users()->attach($admin->id);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/pages/'.$page->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'PAGE_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar la página porque tiene usuarios asignados.');

        $this->assertNotNull(Page::find($page->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Páginas',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function pagesPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'paginas'],
            ['description' => 'Páginas', 'module_id' => $module->id],
        );
    }
}

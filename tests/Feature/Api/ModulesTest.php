<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/modules')->assertStatus(401);
        $this->postJson('/api/mobile/v1/modules', ['description' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/modules')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_modules_with_page_counts_and_summary(): void
    {
        $admin = $this->makeUser('Admin');

        $used = Module::create(['description' => 'Mercado']);
        Page::create([
            'description' => 'Página del módulo',
            'url' => 'pagina-de-'.$used->id,
            'module_id' => $used->id,
        ]);
        $free = Module::create(['description' => 'Módulo libre']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/modules')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $used->id,
                'description' => 'Mercado',
                'pages_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $free->id,
                'description' => 'Módulo libre',
                'pages_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.in_use', 1);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->modulesPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/modules')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_module(): void
    {
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/modules', [
                'description' => '  Nuevo módulo  ',
                'icon' => '  <svg></svg>  ',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('module.description', 'Nuevo módulo')
            ->assertJsonPath('module.icon', '<svg></svg>')
            ->assertJsonPath('module.pages_count', 0)
            ->assertJsonPath('module.can_delete', true);
    }

    public function test_duplicate_and_empty_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        Module::create(['description' => 'Mercado']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/modules', ['description' => 'Mercado'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/modules', ['description' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');
    }

    public function test_update_changes_the_module(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Módulo A']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/modules/'.$module->id, [
                'description' => 'Módulo A Editado',
                'icon' => '<svg>editado</svg>',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('module.description', 'Módulo A Editado')
            ->assertJsonPath('module.icon', '<svg>editado</svg>');

        $this->assertSame('Módulo A Editado', $module->fresh()->description);
    }

    public function test_delete_removes_a_module_without_pages(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Módulo temporal']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/modules/'.$module->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Módulo eliminado correctamente.');

        $this->assertNull(Module::find($module->id));
    }

    public function test_a_module_with_pages_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $module = Module::create(['description' => 'Módulo con páginas']);
        Page::create([
            'description' => 'Página del módulo',
            'url' => 'pagina-de-'.$module->id,
            'module_id' => $module->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/modules/'.$module->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'MODULE_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar el módulo porque tiene páginas asociadas.');

        $this->assertNotNull(Module::find($module->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Módulos',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function modulesPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'modulos'],
            ['description' => 'Módulos', 'module_id' => $module->id],
        );
    }
}

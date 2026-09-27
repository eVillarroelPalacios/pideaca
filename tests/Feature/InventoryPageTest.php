<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Database\Seeders\InventoryPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryPageTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $prestador;

    private TypeUser $cliente;

    private User $prestadorUser;

    private User $clienteUser;

    protected function setUp(): void
    {
        parent::setUp();

        UserStatus::create(['status' => 'Activo']);

        $this->prestador = TypeUser::create(['description' => 'Prestador']);
        $this->cliente = TypeUser::create(['description' => 'Cliente']);

        $this->prestadorUser = User::create([
            'name' => 'Comercio Inventario',
            'email' => 'inventario@example.com',
            'type_user_id' => $this->prestador->id,
            'password' => 'password',
        ]);

        $this->clienteUser = User::create([
            'name' => 'Cliente Inventario',
            'email' => 'cliente.inventario@example.com',
            'type_user_id' => $this->cliente->id,
            'password' => 'password',
        ]);
    }

    public function test_seeder_crea_la_pagina_en_el_circuito_del_modulo(): void
    {
        $this->seed(InventoryPagesSeeder::class);

        $page = Page::where('url', 'inventario')->firstOrFail();

        $this->assertSame('Inventario', $page->description);
        $this->assertSame('Pide acá', optional($page->module)->description);
        $this->assertSame('Pide acá', optional(Module::find($page->module_id))->description);
    }

    public function test_seeder_asigna_la_pagina_al_tipo_prestador_y_no_al_cliente(): void
    {
        $this->seed(InventoryPagesSeeder::class);

        $page = Page::where('url', 'inventario')->firstOrFail();

        $this->assertTrue(
            $this->prestador->assignedPages()->pluck('pages.id')->contains($page->id)
        );
        $this->assertFalse(
            $this->cliente->assignedPages()->pluck('pages.id')->contains($page->id)
        );
    }

    public function test_seeder_propaga_la_pagina_a_los_prestadores_existentes(): void
    {
        $this->seed(InventoryPagesSeeder::class);

        $page = Page::where('url', 'inventario')->firstOrFail();

        $this->assertTrue(
            $this->prestadorUser->pages()->pluck('pages.id')->contains($page->id)
        );
        $this->assertFalse(
            $this->clienteUser->pages()->pluck('pages.id')->contains($page->id)
        );
    }

    public function test_seeder_es_idempotente(): void
    {
        $this->seed(InventoryPagesSeeder::class);
        $this->seed(InventoryPagesSeeder::class);

        $page = Page::where('url', 'inventario')->firstOrFail();

        $this->assertSame(1, Page::where('url', 'inventario')->count());
        $this->assertSame(
            1,
            DB::table('page_user')
                ->where('user_id', $this->prestadorUser->id)
                ->where('page_id', $page->id)
                ->count()
        );
        $this->assertSame(
            1,
            DB::table('page_type_user')
                ->where('type_user_id', $this->prestador->id)
                ->where('page_id', $page->id)
                ->count()
        );
    }

    public function test_dashboard_muestra_la_seccion_de_inventario_al_prestador(): void
    {
        $this->seed(InventoryPagesSeeder::class);

        $response = $this->actingAs($this->prestadorUser)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="dash-inventario"', false);
        $response->assertSee("showDashSection('inventario')", false);
    }

    public function test_dashboard_no_muestra_la_seccion_de_inventario_a_un_cliente(): void
    {
        $this->seed(InventoryPagesSeeder::class);

        $response = $this->actingAs($this->clienteUser)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('id="dash-inventario"', false);
        $response->assertDontSee("showDashSection('inventario')", false);
    }
}

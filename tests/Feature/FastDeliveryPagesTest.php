<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Database\Seeders\FastDeliveryPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FastDeliveryPagesTest extends TestCase
{
    use RefreshDatabase;

    private Module $pideAcaModule;

    private TypeUser $cliente;

    private TypeUser $prestador;

    protected function setUp(): void
    {
        parent::setUp();

        // Estado previo del proyecto: el módulo Pide acá y los tipos de usuario.
        $this->pideAcaModule = Module::create(['description' => 'Pide acá']);
        Module::create(['description' => 'Administrar']);

        $this->cliente = TypeUser::create(['description' => 'Cliente']);
        $this->prestador = TypeUser::create(['description' => 'Prestador']);

        // Página que ya estaba asignada a ambos tipos antes de este módulo.
        $perfil = Page::create([
            'description' => 'Mi Perfil',
            'url' => 'perfil',
            'module_id' => Module::where('description', 'Administrar')->value('id'),
        ]);

        $this->cliente->assignedPages()->attach($perfil);
        $this->prestador->assignedPages()->attach($perfil);
    }

    public function test_seeder_crea_las_paginas_de_fast_delivery_en_la_plantilla_de_cada_tipo(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $this->assertSame(
            ['Comercios', 'Mi Perfil', 'Mis Pedidos'],
            $this->cliente->assignedPages()->orderBy('pages.description')->pluck('description')->all()
        );

        $this->assertSame(
            ['Mi Catálogo', 'Mi Perfil', 'Pedidos'],
            $this->prestador->assignedPages()->orderBy('pages.description')->pluck('description')->all()
        );
    }

    public function test_las_paginas_nuevas_quedan_en_el_modulo_pide_aca_con_url_para_el_menu(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $expected = [
            'Comercios' => 'comercios',
            'Mis Pedidos' => 'mis-pedidos',
            'Mi Catálogo' => 'mi-catalogo',
            'Pedidos' => 'pedidos',
        ];

        foreach ($expected as $description => $url) {
            $page = Page::where('description', $description)->firstOrFail();

            $this->assertSame($url, $page->url, "La página «{$description}» debe tener url {$url}.");
            $this->assertSame(
                $this->pideAcaModule->id,
                (int) $page->module_id,
                "La página «{$description}» debe pertenecer al módulo Pide acá."
            );
        }
    }

    public function test_un_cliente_nuevo_registrado_recibe_las_paginas_de_fast_delivery(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $response = $this->postJson('/registro', [
            'name' => 'Cliente Nuevo',
            'email' => 'cliente.nuevo@example.com',
            'type_user_id' => $this->cliente->id,
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $user = User::where('email', 'cliente.nuevo@example.com')->firstOrFail();

        $this->assertSame(
            ['Comercios', 'Mi Perfil', 'Mis Pedidos'],
            $user->pages()->orderBy('pages.description')->pluck('description')->all()
        );
    }

    public function test_un_prestador_nuevo_registrado_recibe_las_paginas_de_fast_delivery(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $response = $this->postJson('/registro', [
            'name' => 'Prestador Nuevo',
            'email' => 'prestador.nuevo@example.com',
            'type_user_id' => $this->prestador->id,
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $user = User::where('email', 'prestador.nuevo@example.com')->firstOrFail();

        $this->assertSame(
            ['Mi Catálogo', 'Mi Perfil', 'Pedidos'],
            $user->pages()->orderBy('pages.description')->pluck('description')->all()
        );
    }

    public function test_el_seeder_no_toca_las_paginas_de_un_admin(): void
    {
        $admin = TypeUser::create(['description' => 'Admin']);
        $this->seed(FastDeliveryPagesSeeder::class);

        $this->assertCount(0, $admin->assignedPages()->get());
    }

    public function test_el_seeder_no_roba_paginas_asignadas_a_mano(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $user = User::create([
            'name' => 'Prestador Existente',
            'email' => 'prestador.existente@example.com',
            'type_user_id' => $this->prestador->id,
            'password' => bcrypt('secreto123'),
        ]);

        $propia = Page::create([
            'description' => 'Página Propia',
            'url' => 'pagina-propia',
            'module_id' => $this->pideAcaModule->id,
        ]);
        $user->pages()->attach($propia);

        // Segunda corrida del seeder: no debe borrar la página asignada a mano.
        $this->seed(FastDeliveryPagesSeeder::class);

        $this->assertTrue($user->fresh()->pages->contains('id', $propia->id));

        // Y sí debe agregar las del módulo que faltaban.
        $this->assertTrue($user->fresh()->pages->contains('description', 'Mi Catálogo'));
        $this->assertTrue($user->fresh()->pages->contains('description', 'Pedidos'));
    }

    public function test_el_dashboard_de_un_cliente_muestra_el_modulo_pide_aca(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $user = $this->registrar('Cliente Del Menu', 'cliente.menu@example.com', $this->cliente);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pide acá')
            ->assertSee('Comercios')
            ->assertSee('Mis Pedidos')
            // Al cliente no le entra el módulo del prestador: ni en el menú ni en la vista.
            ->assertDontSee("showDashSection('mi-catalogo')", false)
            ->assertDontSee("showDashSection('pedidos')", false)
            ->assertDontSee('id="dash-mi-catalogo"', false)
            ->assertDontSee('id="dash-pedidos"', false);
    }

    public function test_el_dashboard_de_un_prestador_muestra_su_modulo_y_no_el_del_cliente(): void
    {
        $this->seed(FastDeliveryPagesSeeder::class);

        $user = $this->registrar('Prestador Del Menu', 'prestador.menu@example.com', $this->prestador);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pide acá')
            ->assertSee('Mi Catálogo')
            ->assertSee('Pedidos')
            // Al prestador no le entra el módulo del cliente: ni en el menú ni en la vista.
            ->assertDontSee("showDashSection('comercios')", false)
            ->assertDontSee("showDashSection('mis-pedidos')", false)
            ->assertDontSee('id="dash-comercios"', false)
            ->assertDontSee('id="dash-mis-pedidos"', false);
    }

    /**
     * Registra el usuario por el endpoint real para que sus páginas queden
     * asignadas por page_type_user, igual que en producción.
     */
    private function registrar(string $name, string $email, TypeUser $type): User
    {
        $this->postJson('/registro', [
            'name' => $name,
            'email' => $email,
            'type_user_id' => $type->id,
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertOk()->assertJsonPath('success', true);

        return User::where('email', $email)->firstOrFail();
    }
}

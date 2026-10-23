<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceRating;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Solicitar Servicio con el mismo esquema que los comercios: el cliente busca
 * y elige una tarjeta de prestador; la orden se le asigna directo al elegido
 * (merchant_id seteado) en vez de entrar al pool del rubro.
 */
class ServiceProviderBrowseTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private Provider $otherProvider;

    private User $customer;

    private SubGroup $subgroup;

    private Page $paginaSolicitar;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        $cliente = TypeUser::create(['description' => 'Cliente']);
        UserStatus::create(['status' => 'Activo']);

        $moduleB = Module::firstOrCreate(
            ['description' => 'Servicios del Hogar & Bienestar'],
            ['icon' => null]
        );
        $this->paginaSolicitar = Page::firstOrCreate(
            ['description' => 'Solicitar Servicio'],
            ['url' => 'solicitar-servicio', 'module_id' => $moduleB->id]
        );

        $this->providerUser = User::create([
            'name' => 'Gasista Uno',
            'email' => 'gasista@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);
        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Gasista Uno',
            'is_active' => true,
            'zone' => 'Caballito',
        ]);

        $this->otherProviderUser = User::create([
            'name' => 'Electricista Dos',
            'email' => 'electricista@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);
        $this->otherProvider = Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Electricista Dos',
            'is_active' => true,
            'zone' => 'Flores',
        ]);

        $this->customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'maria@example.com',
            'type_user_id' => $cliente->id,
            'password' => 'password',
        ]);
        $this->customer->pages()->attach($this->paginaSolicitar->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);
        config(['catalog.service_group_id' => $group->id]);
        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );
        $electricidad = SubGroup::firstOrCreate(
            ['description' => 'Electricistas', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
        $this->otherProvider->subgroups()->attach($electricidad->id);

        $emergencia = ServiceType::firstOrCreate(
            ['code' => 'EMERGENCY'],
            ['name' => 'Urgencias 24/7', 'sort_order' => 1, 'is_active' => true]
        );
        $cita = ServiceType::firstOrCreate(
            ['code' => 'SCHEDULED'],
            ['name' => 'Cita programada', 'sort_order' => 2, 'is_active' => true]
        );
        $presupuesto = ServiceType::firstOrCreate(
            ['code' => 'QUOTE'],
            ['name' => 'Presupuesto', 'sort_order' => 3, 'is_active' => true]
        );

        // Gasista ofrece urgencia y presupuesto; electricista solo cita.
        $this->provider->serviceTypes()->attach([$emergencia->id, $presupuesto->id]);
        $this->otherProvider->serviceTypes()->attach([$cita->id]);
    }

    protected function tearDown(): void
    {
        try {
            $paths = ServiceOrder::query()->pluck('photo_path')
                ->merge(ServiceOrder::query()->pluck('audio_path'))
                ->filter();

            foreach ($paths as $path) {
                $file = public_path('images/servicios/'.$path);

                if (is_file($file)) {
                    @unlink($file);
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_invitado_recibe_401_en_el_browse(): void
    {
        $this->getJson('/api/v1/service-providers?type=EMERGENCY')->assertStatus(401);
    }

    public function test_browse_acepta_type_opcional_y_rechaza_tipo_invalido(): void
    {
        // Sin type devuelve todos los prestadores del grupo hogar.
        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.providers');

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=NOPE')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_browse_devuelve_solo_prestadores_activos_del_tipo_pedido(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.providers');

        $provider = $response->json('data.providers.0');
        $this->assertSame($this->provider->id, $provider['id']);
        $this->assertSame('Gasista Uno', $provider['business_name']);
        $this->assertSame('Caballito', $provider['zone']);
        $this->assertTrue(collect($provider['service_types'])->contains(fn ($t) => $t['code'] === 'EMERGENCY'));
        $this->assertSame('Gasistas', $provider['subgroups'][0]['description']);
    }

    public function test_browse_filtra_por_tipo_y_por_texto(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=SCHEDULED')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.providers')
            ->assertJsonPath('data.providers.0.id', $this->otherProvider->id);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY&search=Electricista')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.providers');

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY&search=Gasista')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.providers');
    }

    public function test_browse_expone_los_datos_del_encabezado_del_prestador(): void
    {
        // Encabezado de la ventana de urgencias: verificado, tarifa base,
        // calificaciones, urgencias tomadas, disponibilidad y ETA.
        $allDays = collect(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])
            ->mapWithKeys(fn ($day) => [$day => ['open' => true, 'shifts' => [['from' => '00:00', 'to' => '23:59']]]])
            ->all();
        $closedDays = collect(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])
            ->mapWithKeys(fn ($day) => [$day => ['open' => false, 'shifts' => []]])
            ->all();

        $this->provider->update([
            'is_verified' => true,
            'base_visit_fee' => 3500.50,
            'rating' => 4.90,
            'hours' => $allDays,
        ]);
        $this->otherProvider->update(['hours' => $closedDays]);

        // Urgencia despachada hace 20 minutos: define el ETA y el contador.
        $order = ServiceOrder::create([
            'customer_id' => $this->customer->id,
            'sub_group_id' => $this->subgroup->id,
            'merchant_id' => $this->provider->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_PROVIDER_DISPATCHED,
        ]);
        $order->forceFill([
            'created_at' => now()->subMinutes(20),
            'updated_at' => now(),
        ])->save();

        ServiceRating::create([
            'service_order_id' => $order->id,
            'reviewer_id' => $this->customer->id,
            'ratee_id' => $this->providerUser->id,
            'stars' => 5,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers')
            ->assertStatus(200);

        $gasista = collect($response->json('data.providers'))->firstWhere('id', $this->provider->id);

        $this->assertTrue($gasista['is_verified']);
        $this->assertSame(3500.50, (float) $gasista['base_visit_fee']);
        $this->assertSame(1, $gasista['ratings_count']);
        $this->assertSame(1, $gasista['emergency_count']);
        $this->assertTrue($gasista['is_available_now']);
        $this->assertSame('Disponible Ahora', $gasista['availability_label']);
        $this->assertSame(20, $gasista['eta_minutes']);

        $electricista = collect($response->json('data.providers'))->firstWhere('id', $this->otherProvider->id);

        $this->assertFalse($electricista['is_verified']);
        $this->assertNull($electricista['base_visit_fee']);
        $this->assertSame(0, $electricista['emergency_count']);
        $this->assertFalse($electricista['is_available_now']);
        $this->assertSame('Llega en ~30 min', $electricista['availability_label']);
    }

    public function test_browse_marca_disponible_sin_horarios_cargados(): void
    {
        // Sin horarios el prestador se considera disponible las 24 hs.
        $this->provider->update(['hours' => null]);

        $gasista = collect($this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY')
            ->assertStatus(200)
            ->json('data.providers'))
            ->firstWhere('id', $this->provider->id);

        $this->assertTrue($gasista['is_available_now']);
        $this->assertSame('Disponible Ahora', $gasista['availability_label']);
    }

    public function test_dashboard_renderiza_el_encabezado_del_prestador_con_los_nuevos_badges(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('id="fd-solic-chosen-verified"', false)
            ->assertSee('Prestador Verificado', false)
            ->assertSee('id="fd-solic-chosen-status"', false)
            ->assertSee('id="fd-solic-chosen-rating"', false)
            ->assertSee('id="fd-solic-chosen-fee"', false)
            ->assertSee('Tarifa base por urgencia / visita', false)
            ->assertSee('function paintChosenHeader', false)
            ->assertSee('function fdSolicAvailability', false);
    }

    public function test_urgencia_guarda_la_direccion_y_las_coordenadas_del_cliente(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'address' => 'Av. Corrientes 1234, CABA',
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(201);

        $this->assertSame('Av. Corrientes 1234, CABA', $response->json('data.order.address'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $response->json('data.order.id'),
            'address' => 'Av. Corrientes 1234, CABA',
        ]);

        $order = ServiceOrder::find($response->json('data.order.id'));

        $this->assertEquals(-34.61, (float) $order->latitude);
        $this->assertEquals(-58.43, (float) $order->longitude);
    }

    public function test_urgencia_rechaza_una_direccion_demasiado_larga(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'address' => str_repeat('a', 501),
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    public function test_dashboard_renderiza_el_formulario_de_urgencias_ultra_rapido(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Chips de problema rapidos.
            ->assertSee('id="fd-solic-emergency-chips"', false)
            ->assertSee('Pérdida de agua', false)
            ->assertSee('Inundación', false)
            ->assertSee('Sin luz', false)
            ->assertSee('Pérdida de gas', false)
            ->assertSee('window.fdSolicPickChip = function', false)
            // Botones de foto y audio estilizados (sin el "Choose File" default).
            ->assertSee('📷 Tomar / Subir Foto', false)
            ->assertSee('🎙️ Grabar Audio', false)
            ->assertSee('window.fdSolicPickFile = function', false)
            ->assertSee('class="fd-media-btn"', false)
            // Ubicacion actual -> direccion autocompletada.
            ->assertSee('id="fd-solic-emergency-address"', false)
            ->assertSee('Usar mi ubicación actual', false)
            ->assertSee('window.fdSolicUseLocation = function', false)
            ->assertSee('navigator.geolocation', false)
            ->assertSee('nominatim.openstreetmap.org', false);
    }

    public function test_urgencia_guarda_el_medio_de_pago_de_la_tarifa_base(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'payment_method' => 'mercado_pago',
                'description' => 'Pérdida de agua en el baño',
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.payment_method', 'mercado_pago');

        $this->assertDatabaseHas('service_orders', [
            'id' => $response->json('data.order.id'),
            'payment_method' => 'mercado_pago',
        ]);
    }

    public function test_urgencia_rechaza_un_medio_de_pago_invalido(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'payment_method' => 'bitcoin',
                'description' => 'Pérdida de gas',
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_dashboard_renderiza_el_medio_de_pago_y_el_cta_de_urgencia(): void
    {
        $html = $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->getContent();

        // Selector de medio de pago de la tarifa base (3 opciones).
        $this->assertStringContainsString('id="fd-solic-emergency-payment"', $html);
        $this->assertSame(3, substr_count($html, 'class="fd-pay-face"'));
        $this->assertStringContainsString('name="fd-solic-payment"', $html);
        $this->assertStringContainsString('value="mercado_pago"', $html);

        // CTA destacado con el subtitulo de respuesta estimada.
        $this->assertStringContainsString('id="fd-solic-emergency-submit"', $html);
        $this->assertStringContainsString('🚨 SOLICITAR URGENCIA AHORA', $html);
        $this->assertStringContainsString('Respuesta estimada: &lt; 3 min', $html);

        // Validacion previa al envio: chip (categoria) o descripcion.
        $this->assertStringContainsString('function fdSolicEmergencyHasContent', $html);
        $this->assertStringContainsString('function fdSolicPaymentValue', $html);
        $this->assertStringContainsString("querySelector('.fd-chip.is-active')", $html);
    }

    public function test_los_tabs_del_catalogo_quedan_ocultos_al_llenar_urgencias(): void
    {
        $html = $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->getContent();

        // Los tabs Favoritos/Todos y su contenedor estan en la pagina.
        $this->assertStringContainsString('id="fd-solic-tabs"', $html);
        $this->assertStringContainsString('id="fd-solic-tab-favorites"', $html);
        $this->assertStringContainsString('id="fd-solic-tab-all"', $html);

        // Al elegir un prestador (urgencia, cita o presupuesto) se ocultan; al
        // volver al catalogo vuelven a mostrarse (flex, el display original).
        $this->assertStringContainsString(
            "if (fdSolicTabsBar) fdSolicTabsBar.style.display = 'none';",
            $html
        );
        $this->assertStringContainsString("fdSolicTabsBar.style.display = 'flex'", $html);
        // Ya no se ocultan solo para urgencias.
        $this->assertStringNotContainsString("fdSolicService === 'emergency' ? 'none' : 'flex'", $html);
    }

    public function test_browse_devuelve_contadores_de_favoritos_y_total(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/providers/'.$this->provider->id.'/favorite')
            ->assertStatus(200)
            ->assertJsonPath('is_favorite', true)
            ->assertJsonPath('favorites_count', 1);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers')
            ->assertStatus(200)
            ->assertJsonPath('data.favorites_count', 1)
            ->assertJsonPath('data.total_count', 2);

        $gasista = collect($this->actingAs($this->customer)->getJson('/api/v1/service-providers')->json('data.providers'))
            ->firstWhere('id', $this->provider->id);
        $this->assertTrue($gasista['is_favorite']);
    }

    public function test_tab_favoritos_solo_devuelve_los_guardados(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/providers/'.$this->provider->id.'/favorite')
            ->assertStatus(200);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?favorite=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.providers')
            ->assertJsonPath('data.providers.0.id', $this->provider->id);
    }

    public function test_quitar_favorito_lo_saca_del_tab(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/providers/'.$this->provider->id.'/favorite')
            ->assertStatus(200);

        $this->actingAs($this->customer)
            ->postJson('/api/providers/'.$this->provider->id.'/favorite')
            ->assertStatus(200)
            ->assertJsonPath('is_favorite', false)
            ->assertJsonPath('favorites_count', 0);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?favorite=1')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.providers');
    }

    public function test_urgencia_directa_asigna_al_prestador_elegido(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.merchant_id', $this->provider->id)
            ->assertJsonPath('data.order.sub_group.id', $this->subgroup->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED);

        $this->assertDatabaseHas('service_orders', [
            'id' => $response->json('data.order.id'),
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
        ]);
    }

    public function test_cita_directa_asigna_al_prestador_elegido(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->otherProvider->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED);
    }

    public function test_cita_acepta_el_prestador_aunque_el_json_llegue_sin_content_type(): void
    {
        // El formulario mandaba el cuerpo JSON sin la cabecera Content-Type:
        // Laravel no lo parseaba, la peticion llegaba vacia, se perdia el
        // prestador elegido y la validacion pedia el subgrupo.
        $response = $this->actingAs($this->customer)->call(
            'POST',
            '/api/v1/customer/service-orders/scheduled',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
            ])
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->otherProvider->id)
            ->assertJsonPath('data.order.sub_group.id', $this->otherProvider->subgroups()->first()->id);
    }

    public function test_cita_acepta_el_alias_prestador_id(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'prestador_id' => $this->otherProvider->id,
                'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->otherProvider->id);
    }

    public function test_cita_sin_prestador_ni_subgrupo_pide_uno_de_los_dos(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.sub_group_id.0', 'Elegi un prestador o el tipo de servicio que necesitas.');
    }

    public function test_dashboard_manda_el_content_type_json_en_las_citas(): void
    {
        $html = $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->getContent();

        // Sin la cabecera Content-Type el cuerpo JSON no lo parsea Laravel y
        // la peticion llega vacia (se pierde el prestador elegido).
        $this->assertStringContainsString("headers['Content-Type'] = 'application/json';", $html);
        $this->assertStringContainsString("typeof options.body === 'string'", $html);
    }

    public function test_presupuesto_directo_asigna_al_prestador_elegido(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->provider->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED);
    }

    public function test_presupuesto_guarda_el_contexto_dinamico(): void
    {
        $context = json_encode([
            'rubric' => 'plomeria',
            'tags' => ['Pérdida / filtración de agua', 'Termotanque o calefón'],
            'answers' => [
                'property_type' => ['value' => 'departamento', 'label' => 'Departamento'],
                'materials' => ['value' => 'profesional', 'label' => 'Los provee el profesional'],
            ],
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
                'quote_context' => $context,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.quote_context.rubric', 'plomeria')
            ->assertJsonPath('data.order.quote_context.tags.0', 'Pérdida / filtración de agua')
            ->assertJsonPath('data.order.quote_context.answers.property_type.value', 'departamento');

        $order = ServiceOrder::find($response->json('data.order.id'));
        $this->assertSame('plomeria', $order->quote_context['rubric']);
        $this->assertSame('Departamento', $order->quote_context['answers']['property_type']['label']);
    }

    public function test_presupuesto_sin_contexto_deja_la_columna_nula(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
            ])
            ->assertStatus(201);

        $order = ServiceOrder::find($response->json('data.order.id'));
        $this->assertNull($order->quote_context);
    }

    public function test_presupuesto_rechaza_contexto_no_json(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
                'quote_context' => 'esto no es json',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('quote_context');
    }

    public function test_presupuesto_rechaza_contexto_con_forma_invalida(): void
    {
        // tags debe ser un array de textos.
        $context = json_encode(['rubric' => 'plomeria', 'tags' => 'no-array', 'answers' => []]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
                'quote_context' => $context,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('quote_context');
    }

    public function test_presupuesto_guarda_disponibilidad_y_nivel_de_detalle(): void
    {
        $context = json_encode([
            'rubric' => 'electricidad',
            'tags' => ['Cortocircuito'],
            'answers' => [],
            'availability' => ['mode' => 'ventana', 'label' => 'Franja horaria: tarde', 'window' => ['from' => '12:00', 'to' => '18:00']],
            'quality' => ['level' => 'alto', 'score' => 85],
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Revisar tablero electrico',
                'photos' => [UploadedFile::fake()->image('tablero.jpg')],
                'quote_context' => $context,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.quote_context.availability.mode', 'ventana')
            ->assertJsonPath('data.order.quote_context.availability.window.to', '18:00')
            ->assertJsonPath('data.order.quote_context.quality.level', 'alto')
            ->assertJsonPath('data.order.quote_context.quality.score', 85);

        $order = ServiceOrder::find($response->json('data.order.id'));
        $this->assertSame('ventana', $order->quote_context['availability']['mode']);
        $this->assertSame('alto', $order->quote_context['quality']['level']);
    }

    public function test_presupuesto_rechaza_disponibilidad_invalida(): void
    {
        $context = json_encode([
            'rubric' => 'plomeria',
            'tags' => [],
            'answers' => [],
            'availability' => ['mode' => 'cuando-sea'],
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
                'quote_context' => $context,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('quote_context');
    }

    public function test_presupuesto_normaliza_el_puntaje_del_nivel_de_detalle(): void
    {
        // score fuera de rango se recorta a 0..100; nivel invalido se rechaza.
        $context = json_encode([
            'rubric' => 'plomeria',
            'tags' => [],
            'answers' => [],
            'quality' => ['level' => 'medio', 'score' => 250],
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
                'quote_context' => $context,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.quote_context.quality.score', 100);

        $badContext = json_encode([
            'rubric' => 'plomeria',
            'tags' => [],
            'answers' => [],
            'quality' => ['level' => 'altisimo', 'score' => 50],
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
                'quote_context' => $badContext,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quote_context');
    }

    public function test_dashboard_renderiza_el_selector_de_disponibilidad_y_el_nivel_de_detalle(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Selector de disponibilidad + franja horaria.
            ->assertSee('id="fd-solic-quote-availability"', false)
            ->assertSee('name="fd-quote-avail" value="asap"', false)
            ->assertSee('name="fd-quote-avail" value="manana"', false)
            ->assertSee('name="fd-quote-avail" value="ventana"', false)
            ->assertSee('onchange="fdSolicQuotePickAvailability(this)"', false)
            ->assertSee('id="fd-solic-quote-window"', false)
            // Indicador de calidad.
            ->assertSee('id="fd-solic-quote-quality"', false)
            ->assertSee('id="fd-solic-quote-quality-badge"', false)
            ->assertSee('id="fd-solic-quote-quality-fill"', false)
            // Handlers JS.
            ->assertSee('window.fdSolicQuotePickAvailability = function', false)
            ->assertSee('function fdSolicQuoteRenderQuality()', false)
            ->assertSee('function fdSolicQuoteCurrentAvailability()', false)
            // Boton principal con el nuevo label.
            ->assertSee('Enviar Solicitud de Presupuesto', false);
    }

    public function test_el_submit_incluye_disponibilidad_y_nivel_de_detalle(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('fdSolicQuoteSyncState', false)
            ->assertSee('fdSolicQuoteValidate', false)
            ->assertSee('fdSolicQuoteBuildForm', false)
            ->assertSee('fdSolicQuoteResetState', false)
            ->assertSee('fdSolicQuoteState', false)
            ->assertSee('quote_context', false);
    }

    public function test_no_se_puede_elegir_un_prestador_que_no_ofrece_el_tipo(): void
    {
        // Electricista no ofrece EMERGENCY.
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->otherProvider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_el_prestador_asignado_puede_aceptar_su_urgencia_directa(): void
    {
        $order = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(201)
            ->json('data.order');

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$order['id'].'/accept')
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_PROVIDER_DISPATCHED)
            ->assertJsonPath('data.order.merchant_id', $this->provider->id);
    }

    public function test_el_browse_respeta_el_middleware_de_la_pagina(): void
    {
        $sinPagina = User::create([
            'name' => 'Sin Pagina',
            'email' => 'sinpagina@example.com',
            'type_user_id' => TypeUser::first()->id,
            'password' => 'password',
        ]);

        $this->actingAs($sinPagina)
            ->getJson('/api/v1/service-providers?type=EMERGENCY')
            ->assertStatus(403);
    }

    public function test_seguimiento_devuelve_la_orden_propia_con_los_datos_del_prestador(): void
    {
        $this->provider->update(['rating' => 4.80, 'is_verified' => true]);

        $order = ServiceOrder::create([
            'customer_id' => $this->customer->id,
            'sub_group_id' => $this->subgroup->id,
            'merchant_id' => $this->provider->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_REQUESTED,
            'address' => 'Av. Rivadavia 100',
            'payment_method' => 'efectivo',
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders/'.$order->id)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.id', $order->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED)
            ->assertJsonPath('data.order.cancellable', true)
            ->assertJsonPath('data.order.address', 'Av. Rivadavia 100')
            ->assertJsonPath('data.order.merchant.business_name', 'Gasista Uno')
            ->assertJsonPath('data.order.merchant.contact_name', $this->providerUser->name)
            ->assertJsonPath('data.order.merchant.is_verified', true);

        // El seguimiento pinta la foto del prestador aunque todavia no tenga
        // banner cargado (la clave siempre viaja para el card de contacto).
        $this->assertArrayHasKey('photo_url', $response->json('data.order.merchant'));
        // El listado solo ofrece los estados simplificados para filtrar.
        $this->assertSame(
            ['PENDIENTE', 'PRESUPUESTADO', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'],
            $response->json('data.statuses')
        );
    }

    public function test_seguimiento_rechaza_la_orden_de_otro_cliente(): void
    {
        $otro = User::create([
            'name' => 'Otro Cliente',
            'email' => 'otro@example.com',
            'type_user_id' => TypeUser::where('description', 'Cliente')->value('id'),
            'password' => 'password',
        ]);

        $order = ServiceOrder::create([
            'customer_id' => $otro->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders/'.$order->id)
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_seguimiento_exige_estar_autenticado(): void
    {
        $order = ServiceOrder::create([
            'customer_id' => $this->customer->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        $this->getJson('/api/v1/customer/service-orders/'.$order->id)->assertStatus(401);
    }

    public function test_dashboard_renderiza_la_pantalla_de_seguimiento(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('id="fd-solic-track"', false)
            ->assertSee('id="fd-solic-track-body"', false)
            ->assertSee('window.fdSolicTrackStart = function', false)
            ->assertSee('window.fdSolicTrackCancel = function', false)
            ->assertSee('window.fdSolicTrackGoToServices = function', false)
            ->assertSee('function fdSolicTrackStageOf', false)
            ->assertSee('function fdSolicTrackPoll', false)
            // Pasos, aviso de espera y efecto shimmer del temporizador.
            ->assertSee("label: 'Pendiente'", false)
            ->assertSee("label: 'Presupuestado'", false)
            ->assertSee("label: 'En proceso'", false)
            ->assertSee('Contactando al prestador', false)
            ->assertSee('fdTrackShimmer', false)
            // La pantalla arranca encadenada al alta exitosa de la urgencia.
            ->assertSee('fdSolicTrackStart(order', false);
    }

    public function test_dashboard_renderiza_el_calendario_y_las_franjas_de_cita(): void
    {
        $html = $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('id="fd-solic-form-scheduled"', false)
            ->assertSee('id="fd-solic-calendar"', false)
            ->assertSee('id="fd-solic-slots"', false)
            ->assertSee('id="fd-solic-day-nav"', false)
            ->assertSee('id="fd-solic-day-prev"', false)
            ->assertSee('id="fd-solic-day-next"', false)
            ->assertSee('id="fd-solic-calendar-fallback"', false)
            // Funciones del calendario / franjas.
            ->assertSee('window.fdSolicPickDay = function', false)
            ->assertSee('window.fdSolicPickSlot = function', false)
            ->assertSee('window.fdSolicShiftDays = function', false)
            ->assertSee('function fdSolicSchedBuildDays', false)
            ->assertSee('function fdSolicSchedSlotsFor', false)
            ->getContent();

        // Los inputs nativos de fecha/hora ya no existen: ahora son hidden y los
        // elige el calendario/franjas.
        $this->assertStringNotContainsString('type="date"', $html);
        $this->assertStringNotContainsString('type="time"', $html);
        $this->assertStringContainsString('<input type="hidden" id="fd-solic-date"', $html);
        $this->assertStringContainsString('<input type="hidden" id="fd-solic-time"', $html);

        // Horizonte de 14 dias y fallback estandar 09:00-18:00.
        $this->assertStringContainsString('FD_SOLIC_SCHED_DAYS = 14', $html);
        $this->assertStringContainsString("FD_SOLIC_SCHED_FALLBACK = { from: '09:00', to: '18:00' }", $html);

        // El fallback se normaliza a minutos antes de generar turnos: sin esto
        // se concatenaban strings ('09:00'+60) y el bucle nunca terminaba.
        $this->assertStringContainsString(
            'shifts: [{ from: fdSolicMinutes(FD_SOLIC_SCHED_FALLBACK.from), to: fdSolicMinutes(FD_SOLIC_SCHED_FALLBACK.to) }]',
            $html
        );
    }

    public function test_reserva_cita_usa_la_fecha_y_hora_elegidas(): void
    {
        // El formulario manda scheduled_at armado con el dia del calendario y la
        // franja elegida; el backend lo acepta y guarda.
        $dia = now()->addDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => $dia.' 11:30:00',
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->otherProvider->id);

        $this->assertDatabaseHas('service_orders', [
            'id' => $response->json('data.order.id'),
            'scheduled_at' => $dia.' 11:30:00',
        ]);
    }

    public function test_dashboard_renderiza_la_tarjeta_de_sena_y_politicas(): void
    {
        $html = $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Tarjeta explicativa de la sena.
            ->assertSee('La seña del 50% garantiza el turno y se descuenta del saldo final', false)
            ->assertSee('id="fd-solic-deposit"', false)
            // Desglose del pago.
            ->assertSee('Tarifa base de visita', false)
            ->assertSee('id="fd-solic-fee-base"', false)
            ->assertSee('Monto a abonar ahora (Seña 50%)', false)
            ->assertSee('id="fd-solic-fee-now"', false)
            ->assertSee('Restante a pagar en el domicilio', false)
            ->assertSee('id="fd-solic-fee-rest"', false)
            // Politica de cancelacion / reprogramacion.
            ->assertSee('Cancelación/Reprogramación gratuita hasta 24 hs antes', false)
            ->getContent();

        // El desglose se recalcula con la tarifa base del prestador elegido.
        $this->assertStringContainsString('fdSolicSelected.base_visit_fee', $html);
        // La sena es el 50% de la tarifa base.
        $this->assertStringContainsString('Math.round(fee * 50) / 100', $html);
    }

    public function test_el_desglose_de_la_sena_usa_la_tarifa_base_del_prestador(): void
    {
        // La tarifa base viaja en el browse para que el formulario calcule la
        // sena sin pedirla de nuevo.
        $this->otherProvider->update(['base_visit_fee' => 3500]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=SCHEDULED')
            ->assertStatus(200);

        $electricista = collect($response->json('data.providers'))->firstWhere('id', $this->otherProvider->id);

        $this->assertNotNull($electricista);
        $this->assertSame(3500.0, (float) $electricista['base_visit_fee']);
    }

    public function test_dashboard_renderiza_los_datos_de_acceso_y_el_recordatorio(): void
    {
        $html = $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Direccion estructurada.
            ->assertSee('id="fd-solic-address-street"', false)
            ->assertSee('id="fd-solic-address-unit"', false)
            ->assertSee('id="fd-solic-address-neighborhood"', false)
            ->assertSee('Calle y número', false)
            ->assertSee('Piso / Depto', false)
            ->assertSee('Barrio / Zona', false)
            // Canal de recordatorio.
            ->assertSee('id="fd-solic-reminder-chips"', false)
            ->assertSee('id="fd-solic-reminder"', false)
            ->assertSee('data-channel="whatsapp"', false)
            ->assertSee('data-channel="email"', false)
            ->assertSee('window.fdSolicPickReminder = function', false)
            // Notas / instrucciones de acceso.
            ->assertSee('Notas e instrucciones de acceso', false)
            ->assertSee('Instrucciones para el profesional', false)
            ->getContent();

        // El canal por defecto es WhatsApp.
        $this->assertStringContainsString('<input type="hidden" id="fd-solic-reminder" value="whatsapp">', $html);
    }

    public function test_reserva_cita_guarda_datos_de_acceso_y_recordatorio(): void
    {
        $dia = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => $dia.' 09:30:00',
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
                'notes' => 'Tocar timbre del 4B, portón verde.',
                'address_street' => 'Av. Rivadavia 1234',
                'address_unit' => '4º B',
                'address_neighborhood' => 'Caballito',
                'reminder_channel' => 'email',
            ])
            ->assertStatus(201);

        $orderId = $response->json('data.order.id');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orderId,
            'address_street' => 'Av. Rivadavia 1234',
            'address_unit' => '4º B',
            'address_neighborhood' => 'Caballito',
            'reminder_channel' => 'email',
            'address' => 'Av. Rivadavia 1234, 4º B, Caballito',
        ]);

        // El payload del seguimiento expone los campos nuevos.
        $response->assertJsonPath('data.order.address_street', 'Av. Rivadavia 1234')
            ->assertJsonPath('data.order.reminder_channel', 'email');
    }

    public function test_reserva_cita_rechaza_un_canal_de_recordatorio_invalido(): void
    {
        $dia = now()->addDays(3)->format('Y-m-d');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => $dia.' 09:30:00',
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
                'reminder_channel' => 'telegram',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reminder_channel');
    }

    public function test_reserva_cita_sin_datos_de_acceso_deja_la_direccion_nula(): void
    {
        $dia = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => $dia.' 09:30:00',
                'fulfillment' => 'AT_MERCHANT_LOCATION',
                'payment_method' => 'efectivo',
                'payment_mode' => 'FULL',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('service_orders', [
            'id' => $response->json('data.order.id'),
            'address' => null,
            'address_street' => null,
            'reminder_channel' => null,
        ]);
    }

    public function test_dashboard_renderiza_el_cta_de_reserva_y_la_confirmacion(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // CTA principal de envio.
            ->assertSee('id="fd-solic-scheduled-submit"', false)
            ->assertSee('Reservar cita y pagar seña', false)
            // Vista de confirmacion posterior al envio.
            ->assertSee('id="fd-solic-scheduled-confirm"', false)
            ->assertSee('id="fd-solic-scheduled-confirm-body"', false)
            ->assertSee('window.fdSolicSchedConfirmStart = function', false)
            ->assertSee('window.fdSolicSchedConfirmServices = function', false)
            ->assertSee('window.fdSolicSchedConfirmAgain = function', false)
            // Google Calendar.
            ->assertSee('function fdSolicSchedGcalUrl', false)
            ->assertSee('https://calendar.google.com/calendar/render?action=TEMPLATE', false)
            ->assertSee('Añadir a Google Calendar', false)
            // Estado de la cita.
            ->assertSee('Pendiente de confirmación por el prestador', false)
            ->assertSee('Te notificamos por WhatsApp', false)
            // Resumen del turno.
            ->assertSee('class="fd-conf-lbl">Fecha', false)
            ->assertSee('class="fd-conf-lbl">Hora', false)
            ->assertSee('class="fd-conf-lbl">Dirección', false);
    }

    public function test_la_confirmacion_se_dispara_al_crear_la_cita(): void
    {
        $dia = now()->addDays(4)->format('Y-m-d');

        // El endpoint devuelve la orden con los datos que la confirmacion
        // necesita para el resumen y el link de Google Calendar.
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => $dia.' 14:00:00',
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
                'address_street' => 'Av. Rivadavia 1234',
                'address_unit' => '4º B',
                'address_neighborhood' => 'Caballito',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.address', 'Av. Rivadavia 1234, 4º B, Caballito')
            ->assertJsonStructure(['data' => ['order' => ['scheduled_at', 'address', 'merchant' => ['contact_name']]]]);
    }

    public function test_dashboard_carga_la_configuracion_dinamica_de_presupuestos(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Configuracion por rubro + fallback + resolver.
            ->assertSee('var FD_QUOTE_CONFIGS = {', false)
            ->assertSee('var FD_QUOTE_FALLBACK = {', false)
            ->assertSee('function fdQuoteSlugOf(provider)', false)
            ->assertSee('function fdQuoteConfigFor(provider)', false)
            // Rubros base presentes.
            ->assertSee("slug: 'plomeria'", false)
            ->assertSee("slug: 'electricidad'", false)
            ->assertSee("slug: 'pintura'", false)
            ->assertSee("slug: 'generic'", false)
            // Estructura esperada de cada rubro.
            ->assertSee('quickTags:', false)
            ->assertSee('questions:', false)
            ->assertSee('placeholders:', false)
            // Keywords para resolver el rubro por descripcion del subgrupo.
            ->assertSee("keywords: ['plom'", false);
    }

    public function test_dashboard_renderiza_los_contenedores_del_presupuesto_contextual(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Contenedores dinamicos dentro del form de presupuesto.
            ->assertSee('id="fd-solic-quote-context"', false)
            ->assertSee('id="fd-solic-quote-cat-label"', false)
            ->assertSee('id="fd-solic-quote-tags"', false)
            ->assertSee('id="fd-solic-quote-questions"', false)
            ->assertSee('id="fd-solic-quote-summary"', false)
            // Renderer y handlers de chips/preguntas.
            ->assertSee('window.fdSolicRenderQuote = function', false)
            ->assertSee('function fdSolicRenderQuoteTags', false)
            ->assertSee('function fdSolicRenderQuoteQuestions', false)
            ->assertSee('window.fdSolicToggleQuoteTag = function', false)
            ->assertSee('window.fdSolicPickQuoteAnswer = function', false)
            ->assertSee('function fdSolicQuoteSummary', false);
    }

    public function test_el_presupuesto_envia_el_contexto_dinamico(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // El submit construye quote_context desde el estado unificado.
            ->assertSee("form.append('quote_context', JSON.stringify(", false)
            ->assertSee('rubric: s.rubric', false)
            ->assertSee('tags: s.tags || []', false)
            ->assertSee('answers: s.answers || {}', false)
            ->assertSee('availability: s.availability', false)
            ->assertSee('quality: s.quality', false);
    }

    public function test_dashboard_renderiza_la_zona_de_arrastrar_y_soltar(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Dropzone + inputs ocultos + contenedores de preview.
            ->assertSee('id="fd-solic-quote-dropzone"', false)
            ->assertSee('onclick="return fdSolicQuoteOpenPicker()"', false)
            ->assertSee('ondrop="return fdSolicQuoteDrop(event)"', false)
            ->assertSee('id="fd-solic-quote-photo-grid"', false)
            ->assertSee('id="fd-solic-quote-video-preview"', false)
            // El input file generico ya no es visible.
            ->assertSee('id="fd-solic-quote-photos" accept="image/jpeg,image/png,image/webp" multiple style="display:none;"', false)
            ->assertSee('id="fd-solic-quote-video" accept="video/mp4,video/webm,video/quicktime" style="display:none;"', false)
            // Handlers del componente.
            ->assertSee('window.fdSolicQuoteDrop = function', false)
            ->assertSee('window.fdSolicQuoteAddPhotos = function', false)
            ->assertSee('window.fdSolicQuoteRemovePhoto = function', false)
            ->assertSee('window.fdSolicQuoteSetVideo = function', false)
            ->assertSee('window.fdSolicQuoteRemoveVideo = function', false)
            // Limites declarados (5 fotos x 5 MB, video 20 MB).
            ->assertSee('FD_SOLIC_QUOTE_MAX_PHOTOS = 5', false)
            ->assertSee('FD_SOLIC_QUOTE_MAX_PHOTO_BYTES = 5 * 1024 * 1024', false)
            ->assertSee('FD_SOLIC_QUOTE_MAX_VIDEO_BYTES = 20 * 1024 * 1024', false);
    }

    public function test_el_submit_del_presupuesto_arma_el_formdata_desde_los_archivos(): void
    {
        $this->actingAs($this->customer)
            ->get('/dashboard')
            ->assertStatus(200)
            // Fotos y video salen del estado unificado (buildForm).
            ->assertSee('fdSolicQuoteBuildForm', false)
            ->assertSee("form.append('photos[]', file, file.name)", false)
            ->assertSee("form.append('video', s.video, s.video.name)", false);
    }
}

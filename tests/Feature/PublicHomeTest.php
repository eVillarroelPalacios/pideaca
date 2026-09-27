<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Provider;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $prestadorType;

    private UserStatus $userStatus;

    private Group $activeGroup;

    private Group $inactiveGroup;

    private SubGroup $activeSubGroup;

    private SubGroup $inactiveSubGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestadorType = TypeUser::create(['description' => 'Prestador']);
        $this->userStatus = UserStatus::firstOrCreate(['status' => 'Activo']);

        $this->activeGroup = Group::create([
            'description' => 'Grupo Activo Demo',
            'group_status_id' => GroupStatus::firstOrCreate(['description' => GroupStatus::STATUS_ACTIVE])->id,
        ]);

        $this->inactiveGroup = Group::create([
            'description' => 'Grupo Cerrado Demo',
            'group_status_id' => GroupStatus::firstOrCreate(['description' => GroupStatus::STATUS_INACTIVE])->id,
        ]);

        $this->activeSubGroup = SubGroup::create(['description' => 'Sub Grupo Activo', 'group_id' => $this->activeGroup->id]);
        $this->inactiveSubGroup = SubGroup::create(['description' => 'Sub Grupo Cerrado', 'group_id' => $this->inactiveGroup->id]);
    }

    private function createAdvertisedProvider(string $businessName, array $attributes = []): Provider
    {
        $user = User::create([
            'name' => $businessName,
            'email' => str()->slug($businessName) . '@example.com',
            'type_user_id' => $this->prestadorType->id,
            'user_status_id' => $this->userStatus->id,
            'password' => 'password',
        ]);

        $provider = Provider::create(array_merge([
            'user_id' => $user->id,
            'business_name' => $businessName,
            'is_active' => true,
        ], $attributes));

        return $provider;
    }

    public function test_home_only_shows_active_groups_in_filters_and_service_structure(): void
    {
        $this->createAdvertisedProvider('Comercio Con Grupo Activo')
            ->subgroups()->attach($this->activeSubGroup->id);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Estructura de Servicios')
            ->assertSee('Grupo Activo Demo')
            ->assertDontSee('Grupo Cerrado Demo');
    }

    public function test_home_hides_providers_that_only_belong_to_deactivated_groups(): void
    {
        $this->createAdvertisedProvider('Comercio Con Grupo Activo')
            ->subgroups()->attach($this->activeSubGroup->id);

        $this->createAdvertisedProvider('Comercio Solo Grupo Cerrado')
            ->subgroups()->attach($this->inactiveSubGroup->id);

        $this->createAdvertisedProvider('Comercio Con Categoria Cerrada', [
            'category_id' => $this->inactiveGroup->id,
        ]);

        $sinGrupos = $this->createAdvertisedProvider('Comercio Sin Grupos');

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Comercio Con Grupo Activo')
            ->assertSee($sinGrupos->business_name)
            ->assertDontSee('Comercio Solo Grupo Cerrado')
            ->assertDontSee('Comercio Con Categoria Cerrada');
    }

    public function test_home_keeps_a_deactivated_group_out_of_the_services_section(): void
    {
        $this->createAdvertisedProvider('Comercio Con Grupo Activo')
            ->subgroups()->attach($this->activeSubGroup->id);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Nuestros Servicios')
            ->assertSee('Descubrí las grandes familias')
            ->assertDontSee('Auxilio Vial:');
    }

    public function test_home_keeps_providers_and_groups_without_status_visible(): void
    {
        $sinEstado = Group::create(['description' => 'Grupo Sin Estado']);
        $subGrupo = SubGroup::create(['description' => 'Sub Sin Estado', 'group_id' => $sinEstado->id]);

        $this->createAdvertisedProvider('Comercio Sin Estado De Grupo')
            ->subgroups()->attach($subGrupo->id);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Comercio Sin Estado De Grupo')
            ->assertSee('Grupo Sin Estado');
    }

    public function test_home_footer_shows_only_active_group_icons(): void
    {
        $this->activeGroup->update(['icon' => '<svg data-footer="activo"></svg>']);
        $this->inactiveGroup->update(['icon' => '<svg data-footer="cerrado"></svg>']);

        $html = $this->get('/')->assertOk()->getContent();
        $pos = strpos($html, '<footer');
        $this->assertNotFalse($pos);
        $footer = substr($html, $pos);

        $this->assertStringContainsString('data-footer="activo"', $footer);
        $this->assertStringNotContainsString('data-footer="cerrado"', $footer);
        $this->assertSame(1, substr_count($footer, 'class="footer-group-icon"'));
    }
}

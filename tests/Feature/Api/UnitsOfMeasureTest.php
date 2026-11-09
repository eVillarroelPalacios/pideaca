<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Module;
use App\Models\Page;
use App\Models\Product;
use App\Models\Provider;
use App\Models\TypeUser;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitsOfMeasureTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/units-of-measure')->assertStatus(401);
        $this->postJson('/api/mobile/v1/units-of-measure', ['name' => 'X'])->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/units-of-measure')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_lists_units_with_usage_counts_and_summary(): void
    {
        $admin = $this->makeUser('Admin');

        $used = UnitOfMeasure::create([
            'name' => 'Kilogramo',
            'symbol' => 'kg',
            'base_conversion_factor' => 1,
            'is_integer_only' => false,
        ]);
        $provider = Provider::create([
            'user_id' => $admin->id,
            'business_name' => 'Almacén',
            'is_active' => true,
        ]);
        $category = Category::create(['provider_id' => $provider->id, 'name' => 'General']);
        Product::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'unit_of_measure_id' => $used->id,
            'name' => 'Harina',
            'price' => 100,
        ]);

        $free = UnitOfMeasure::create([
            'name' => 'Unidad',
            'symbol' => 'u',
            'base_conversion_factor' => 1,
            'is_integer_only' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/units-of-measure')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $used->id,
                'name' => 'Kilogramo',
                'symbol' => 'kg',
                'products_count' => 1,
                'can_delete' => false,
            ])
            ->assertJsonFragment([
                'id' => $free->id,
                'name' => 'Unidad',
                'products_count' => 0,
                'can_delete' => true,
            ])
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.in_use', 1);
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->unitsPage()->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/units-of-measure')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_a_trimmed_unit(): void
    {
        $admin = $this->makeUser('Admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/units-of-measure', [
                'name' => '  Litro  ',
                'symbol' => '  l  ',
                'base_conversion_factor' => 1,
                'is_integer_only' => false,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('unit.name', 'Litro')
            ->assertJsonPath('unit.symbol', 'l')
            ->assertJsonPath('unit.base_conversion_factor', 1)
            ->assertJsonPath('unit.is_integer_only', false)
            ->assertJsonPath('unit.can_delete', true);
    }

    public function test_duplicate_name_symbol_and_bad_factor_are_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        UnitOfMeasure::create([
            'name' => 'Kilogramo',
            'symbol' => 'kg',
            'base_conversion_factor' => 1,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/units-of-measure', [
                'name' => 'Kilogramo',
                'symbol' => 'kilo',
                'base_conversion_factor' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/units-of-measure', [
                'name' => 'Kilo',
                'symbol' => 'kg',
                'base_conversion_factor' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('symbol');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/units-of-measure', [
                'name' => 'Gramo',
                'symbol' => 'g',
                'base_conversion_factor' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('base_conversion_factor');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/units-of-measure', [
                'name' => '   ',
                'symbol' => 'g',
                'base_conversion_factor' => 0.001,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_changes_the_unit(): void
    {
        $admin = $this->makeUser('Admin');
        $unit = UnitOfMeasure::create([
            'name' => 'Kilo',
            'symbol' => 'k',
            'base_conversion_factor' => 1,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/mobile/v1/units-of-measure/'.$unit->id, [
                'name' => 'Kilogramo',
                'symbol' => 'kg',
                'base_conversion_factor' => 1,
                'is_integer_only' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unit.name', 'Kilogramo')
            ->assertJsonPath('unit.symbol', 'kg')
            ->assertJsonPath('unit.is_integer_only', false);

        $this->assertSame('Kilogramo', $unit->fresh()->name);
    }

    public function test_delete_removes_a_unit_without_usages(): void
    {
        $admin = $this->makeUser('Admin');
        $unit = UnitOfMeasure::create([
            'name' => 'Unidad temporal',
            'symbol' => 'ut',
            'base_conversion_factor' => 1,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/units-of-measure/'.$unit->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Unidad de medida eliminada correctamente.');

        $this->assertNull(UnitOfMeasure::find($unit->id));
    }

    public function test_a_unit_used_by_products_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $unit = UnitOfMeasure::create([
            'name' => 'Kilogramo',
            'symbol' => 'kg',
            'base_conversion_factor' => 1,
        ]);
        $provider = Provider::create([
            'user_id' => $admin->id,
            'business_name' => 'Almacén',
            'is_active' => true,
        ]);
        $category = Category::create(['provider_id' => $provider->id, 'name' => 'General']);
        Product::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'unit_of_measure_id' => $unit->id,
            'name' => 'Harina',
            'price' => 100,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/mobile/v1/units-of-measure/'.$unit->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNIT_IN_USE')
            ->assertJsonPath('message', 'No se puede eliminar la unidad porque la usan productos.');

        $this->assertNotNull(UnitOfMeasure::find($unit->id));
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $email = strtolower($type).'-'.uniqid('', true).'@example.com';

        return User::create([
            'name' => $type.' Unidades',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'password' => 'secret123',
        ]);
    }

    private function unitsPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'unidades-medida'],
            ['description' => 'Unid. Medidas', 'module_id' => $module->id],
        );
    }
}

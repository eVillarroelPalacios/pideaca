<?php

namespace Tests\Feature\Api;

use App\Models\ContactInquiry;
use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInquiriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/contact-inquiries')->assertStatus(401);
    }

    public function test_rejects_users_without_the_page_permission(): void
    {
        $client = $this->makeUser('Cliente');

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/contact-inquiries')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_list_with_counts_and_status_filter(): void
    {
        // El Admin omite la validacion de paginas, igual que en la web.
        $admin = $this->makeUser('Admin');

        $this->makeInquiry('Ana', 'new');
        $this->makeInquiry('Beto', 'in_progress');
        $this->makeInquiry('Caro', 'resolved');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/contact-inquiries')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('counts.all', 3)
            ->assertJsonPath('counts.new', 1)
            ->assertJsonPath('counts.in_progress', 1)
            ->assertJsonPath('counts.resolved', 1)
            ->assertJsonPath('filters.statuses', ['new', 'in_progress', 'resolved']);

        $this->assertCount(3, $response->json('data'));

        $filtered = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/contact-inquiries?status=new')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            // Los conteos siempre reflejan el total por estado, no el filtro.
            ->assertJsonPath('counts.all', 3);

        $this->assertSame('Ana', $filtered->json('data.0.full_name'));
        $this->assertSame('new', $filtered->json('data.0.status'));
    }

    public function test_an_assigned_user_can_access(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->contactInquiriesPage()->id]);

        $this->makeInquiry('Ana', 'new');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mobile/v1/contact-inquiries')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_show_returns_the_inquiry_detail(): void
    {
        $admin = $this->makeUser('Admin');
        $inquiry = $this->makeInquiry('Ana', 'new', 'sugerencia', 'Me gustaria mas cobertura.');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/mobile/v1/contact-inquiries/'.$inquiry->id)
            ->assertOk()
            ->assertJsonPath('data.contact_inquiry.full_name', 'Ana')
            ->assertJsonPath('data.contact_inquiry.email', 'ana@example.com')
            ->assertJsonPath('data.contact_inquiry.inquiry_type', 'sugerencia')
            ->assertJsonPath('data.contact_inquiry.message', 'Me gustaria mas cobertura.')
            ->assertJsonPath('data.contact_inquiry.status', 'new')
            ->assertJsonPath('data.statuses', ['new', 'in_progress', 'resolved']);
    }

    public function test_update_status_changes_the_record(): void
    {
        $admin = $this->makeUser('Admin');
        $inquiry = $this->makeInquiry('Ana', 'new');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/contact-inquiries/'.$inquiry->id.'/status', [
                'status' => 'resolved',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Estado actualizado correctamente.')
            ->assertJsonPath('contact_inquiry.status', 'resolved');

        $this->assertSame('resolved', $inquiry->fresh()->status);
    }

    public function test_update_status_rejects_an_invalid_value(): void
    {
        $admin = $this->makeUser('Admin');
        $inquiry = $this->makeInquiry('Ana', 'new');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/mobile/v1/contact-inquiries/'.$inquiry->id.'/status', [
                'status' => 'archived',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame('new', $inquiry->fresh()->status);
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);
        $email = strtolower($type).'-'.uniqid().'@example.com';

        return User::create([
            'name' => $type.' Contactos',
            'email' => $email,
            'type_user_id' => $typeUser->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function contactInquiriesPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'consultas-sugerencias'],
            ['description' => 'Consultas y sugerencias', 'module_id' => $module->id],
        );
    }

    private function makeInquiry(
        string $name,
        string $status,
        string $type = 'consulta',
        string $message = 'Mensaje de prueba.',
    ): ContactInquiry {
        return ContactInquiry::create([
            'full_name' => $name,
            'email' => strtolower($name).'@example.com',
            'inquiry_type' => $type,
            'message' => $message,
            'status' => $status,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackSiteVisit;
use App\Models\Module;
use App\Models\Page;
use App\Models\SiteVisit;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SiteVisitsStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_registra_una_sola_visita_por_ip_y_dia(): void
    {
        $middleware = new TrackSiteVisit;
        $next = fn ($request) => response('ok');

        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_USER_AGENT' => 'QA-Browser/1.0',
        ]);

        $middleware->handle($request, $next);
        $middleware->handle($request, $next);

        $this->assertDatabaseCount('site_visits', 1);
        $this->assertSame(hash('sha256', '203.0.113.9'), (string) SiteVisit::first()->ip_hash);
        $this->assertSame(today()->toDateString(), SiteVisit::first()->visited_on->toDateString());
    }

    public function test_middleware_ignora_bots_y_rutas_que_no_son_landing(): void
    {
        $middleware = new TrackSiteVisit;
        $next = fn ($request) => response('ok');

        $bot = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
        ]);
        $middleware->handle($bot, $next);

        $other = Request::create('/delivery', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.11',
            'HTTP_USER_AGENT' => 'QA-Browser/1.0',
        ]);
        $middleware->handle($other, $next);

        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_dashboard_envia_stats_reales_con_la_pagina_asignada(): void
    {
        $user = $this->makeUser('Cliente');
        $user->pages()->sync([$this->consultsPage()->id]);

        $sixDaysAgo = today()->subDays(6)->toDateString();
        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3'] as $ip) {
            $this->makeVisit($ip, 'MX', 'México', today()->toDateString());
        }
        foreach (['10.0.0.4', '10.0.0.5'] as $ip) {
            $this->makeVisit($ip, 'AR', 'Argentina', today()->toDateString());
        }
        $this->makeVisit('10.0.0.6', 'ES', 'España', $sixDaysAgo);

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();

        $stats = $response->original->getData()['visitStats'];
        $this->assertSame(6, $stats['total']);
        $this->assertSame(5, $stats['today']);
        $this->assertSame(3, $stats['dailyAvg']);

        $d7 = $stats['ranges']['d7']['countries'];
        $this->assertSame(['MX', 'AR', 'ES'], array_column($d7, 'code'));
        $this->assertSame([50, 33, 17], array_column($d7, 'pct'));
        $this->assertSame('🇲🇽', $d7[0]['flag']);
        $this->assertSame(2, $d7[0]['dailyAvg']);

        $this->assertCount(7, $stats['ranges']['d7']['days']);
        $this->assertSame(1, $stats['ranges']['d7']['days'][0]['visits']);
        $this->assertSame(5, $stats['ranges']['d7']['days'][6]['visits']);
        $this->assertCount(1, $stats['ranges']['today']['days']);
        $this->assertCount(30, $stats['ranges']['d30']['days']);
        $this->assertSame(['MX', 'AR'], array_column($stats['ranges']['today']['countries'], 'code'));
    }

    public function test_dashboard_no_calcula_stats_sin_la_pagina(): void
    {
        $user = $this->makeUser('Cliente');

        $response = $this->actingAs($user)->get('/');
        $response->assertOk();

        $this->assertNull($response->original->getData()['visitStats'] ?? null);
    }

    private function makeVisit(string $ip, string $code, string $name, string $day): SiteVisit
    {
        return SiteVisit::create([
            'ip_hash' => hash('sha256', $ip.$day),
            'visited_on' => $day,
            'ip' => $ip,
            'country_code' => $code,
            'country_name' => $name,
            'user_agent' => 'QA-Browser/1.0',
        ]);
    }

    private function makeUser(string $type): User
    {
        $typeUser = TypeUser::firstOrCreate(['description' => $type]);
        $status = UserStatus::firstOrCreate(['status' => 'Activo']);

        return User::create([
            'name' => $type.' Visitas',
            'email' => strtolower($type).'-'.uniqid().'@example.com',
            'type_user_id' => $typeUser->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }

    private function consultsPage(): Page
    {
        $module = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(
            ['url' => 'consultas-sugerencias'],
            ['description' => 'Consultas y sugerencias', 'module_id' => $module->id],
        );
    }
}

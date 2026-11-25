<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los uploads de prueba usan nombres deterministicos por id
        // (quote_{id}_foto_{i}.jpg, urgencia_{id}_foto.jpg) y con SQLite en
        // memoria los ids reinician en 1 en cada test: se limpian los
        // restos de corridas anteriores para que no colisionen.
        // (Solo basura de tests: el dir esta gitignoreado.)
        $legacy = public_path('images/servicios');

        if (is_dir($legacy)) {
            foreach ((array) glob($legacy.'/{quote_*,urgencia_*,completada_*}', GLOB_BRACE) as $stale) {
                if (is_file($stale)) {
                    @unlink($stale);
                }
            }
        }
    }
}

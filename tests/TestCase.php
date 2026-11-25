<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['bookings.uploads_dir' => sys_get_temp_dir().'/qa-uploads']);
        // Los uploads de prueba usan nombres deterministicos por id
        // (quote_{id}_foto_{i}.jpg) y con SQLite en memoria los ids
        // reinician en 1 en cada test: se redirigen a un temporal fuera
        // del arbol indexado (el indexador de Windows traba el rename en
        // rafaga) y se limpia antes de cada test.
        $dir = config('bookings.uploads_dir', public_path('images/servicios'));

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ((array) glob($dir.'/quote_*') as $stale) {
            if (is_file($stale)) {
                @unlink($stale);
            }
        }

        // Limpieza legacy del dir real por si quedaron de corridas viejas.
        $legacy = public_path('images/servicios');

        if ($legacy !== $dir && is_dir($legacy)) {
            foreach ((array) glob($legacy.'/quote_*') as $stale) {
                if (is_file($stale)) {
                    @unlink($stale);
                }
            }
        }
    }
}

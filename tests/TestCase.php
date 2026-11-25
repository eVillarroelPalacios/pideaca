<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los uploads usan nombres deterministicos por id (quote_{id}_...)
        // y con SQLite en memoria los ids reinician en 1 en cada test.
        // Cada test escribe en SU PROPIO directorio temporal: asi nunca se
        // borra ni se recrea el mismo nombre (Windows niega recrear un
        // archivo con borrado pendiente si otro proceso aun lo tiene
        // abierto, lo que fallaba de forma intermitente).
        $dir = sys_get_temp_dir().'/qa-uploads-'.uniqid('', true);
        mkdir($dir, 0755, true);
        config(['bookings.uploads_dir' => $dir]);

        // Limpieza legacy del arbol real por si quedaron de corridas viejas.
        $legacy = public_path('images/servicios');

        if (is_dir($legacy)) {
            foreach ((array) glob($legacy.'/quote_*') as $stale) {
                if (is_file($stale)) {
                    @unlink($stale);
                }
            }
        }
    }
}

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
        // memoria los ids reinician en 1 en cada test. NO se borra nada
        // entre tests: la sobreescritura directa es atomica y confiable,
        // mientras que borrar+recrear hace que Windows niegue la recreacion
        // si otro proceso (indexador/antivirus) aun tiene abierto el archivo.
        // (Solo basura de tests: el dir esta gitignoreado.)
    }
}

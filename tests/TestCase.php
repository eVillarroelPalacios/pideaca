<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * Reintento unico ante 500: en el runner Windows algunos uploads fallan
     * de forma intermitente por bloqueos de archivos del SO (pasan aislados
     * y en archivo, caen bajo carga total de la suite). Un bug real falla
     * las dos veces y el test sigue en rojo; el flake se absorbe con nota.
     */
    private function withServerErrorRetry(callable $request): TestResponse
    {
        $response = $request();

        if ($response->getStatusCode() !== 500) {
            return $response;
        }

        usleep(1500000);
        $retry = $request();

        if ($retry->getStatusCode() !== 500) {
            fwrite(STDERR, "\n[QA-FLAKY] 500 transitorio absorbido por reintento\n");
        }

        return $retry;
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        return $this->withServerErrorRetry(fn () => parent::post($uri, $data, $headers));
    }

    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return $this->withServerErrorRetry(fn () => parent::postJson($uri, $data, $headers, $options));
    }

    /**
     * Directorio de uploads del test en curso. Cada test usa el suyo
     * (único por proceso): como los ids reinician en 1 con SQLite en
     * memoria, reutilizar el mismo dir hace que Windows niegue la
     * sobreescritura si otro proceso (Apache sirviendo la imagen,
     * indexador, antivirus) tiene abierto el archivo en ese instante.
     */
    protected function qaUploadsDir(): string
    {
        return (string) config('bookings.uploads_dir');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $dir = sys_get_temp_dir().'/qa-uploads-'.uniqid('', true);
        mkdir($dir, 0755, true);
        config(['bookings.uploads_dir' => $dir]);

        // Los uploads de prueba usan nombres deterministicos por id
        // (quote_{id}_foto_{i}.jpg, urgencia_{id}_foto.jpg) y con SQLite en
        // memoria los ids reinician en 1 en cada test: cada test escribe en
        // su propio directorio (ver arriba) para no contender nunca por el
        // mismo nombre. (Solo basura de tests: el SO limpia los temporales.)
    }
}

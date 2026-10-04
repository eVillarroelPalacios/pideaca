<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    private const CUSTOM_STRINGS = [
        'Server Error',
        '500',
        'Regresar a la P',
        'Verificar Estado del Sistema',
        'PideAca &copy; Copyright',
    ];

    public function test_500_error_view_renders_the_custom_page(): void
    {
        $html = view('errors.500')->render();

        foreach (self::CUSTOM_STRINGS as $needle) {
            $this->assertStringContainsString($needle, $html);
        }

        $this->assertStringContainsString('href="' . url('/') . '"', $html);
        $this->assertStringContainsString('href="' . url('/up') . '"', $html);
    }

    public function test_abort_500_uses_the_custom_error_page(): void
    {
        Route::get('/__test-abort-500', function () {
            abort(500);
        });

        $response = $this->get('/__test-abort-500');

        $response->assertStatus(500);

        foreach (self::CUSTOM_STRINGS as $needle) {
            $response->assertSee($needle, false);
        }
    }

    public function test_unexpected_exception_renders_the_custom_page_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        Route::get('/__test-throw-500', function () {
            throw new RuntimeException('Fallo inesperado del servidor.');
        });

        $response = $this->get('/__test-throw-500');

        $response->assertStatus(500);

        foreach (self::CUSTOM_STRINGS as $needle) {
            $response->assertSee($needle, false);
        }

        $response->assertDontSee('Fallo inesperado del servidor.');
    }
}

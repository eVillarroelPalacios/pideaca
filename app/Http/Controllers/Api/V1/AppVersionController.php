<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Versionado propio de la app Android (Fase 3, $0 sin EAS Update).
 *
 * Endpoint publico: la app instalada lo consulta al arrancar y compara
 * version_code con el suyo. Si el servidor trae un codigo mayor, muestra
 * el aviso y abre el apk_url en el navegador para instalar.
 *
 * Valores desde config/app.php (vars APP_ANDROID_* del .env).
 */
class AppVersionController extends Controller
{
    public function show(): JsonResponse
    {
        $versionCode = (int) config('app.android_version_code', 1);
        $apkUrl = trim((string) config('app.android_apk_url', ''));

        return response()->json([
            'success' => true,
            'data' => [
                'platform' => 'android',
                'package' => 'com.pideaca.app',
                'version_name' => (string) config('app.android_version_name', '1.0.0'),
                'version_code' => $versionCode,
                // Vacio = APK aun no publicado (la app no ofrece descarga).
                'apk_url' => $apkUrl !== '' ? $apkUrl : null,
                'apk_published' => $apkUrl !== '',
                'mandatory' => (bool) config('app.android_mandatory', false),
                'release_notes' => (string) config('app.android_release_notes', ''),
            ],
        ]);
    }
}

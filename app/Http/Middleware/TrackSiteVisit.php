<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    /**
     * Registra una visita unica (IP + dia) solo para invitados en la
     * landing publica. Un unico INSERT IGNORE: si falla, la pagina igual
     * carga (el tracking nunca rompe la respuesta).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->track($request);

        return $next($request);
    }

    private function track(Request $request): void
    {
        if (! $request->isMethod('get') || $request->path() !== '/') {
            return;
        }

        if (Auth::check() || $request->expectsJson()) {
            return;
        }

        $userAgent = (string) $request->userAgent();
        if ($userAgent !== '' && preg_match('/bot|crawl|spider|slurp|mediapartners|baidu|yandex|sogou|exabot|facebot|ia_archiver|pingdom|uptimerobot/i', $userAgent)) {
            return;
        }

        try {
            $ip = $request->ip() ?? '0.0.0.0';
            $countryCode = $request->header('CF-IPCountry');
            $countryCode = is_string($countryCode) && preg_match('/^[A-Za-z]{2}$/', $countryCode)
                ? strtoupper($countryCode)
                : null;

            SiteVisit::insertOrIgnore([[
                'ip_hash' => hash('sha256', $ip),
                'visited_on' => today()->toDateString(),
                'ip' => substr($ip, 0, 45),
                'country_code' => $countryCode,
                'country_name' => null,
                'user_agent' => $userAgent === '' ? null : substr($userAgent, 0, 255),
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        } catch (\Throwable) {
            // Tracking best-effort: nunca interrumpe la respuesta.
        }
    }
}

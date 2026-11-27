<?php

namespace App\Console\Commands;

use App\Models\SiteVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeolocateVisits extends Command
{
    protected $signature = 'visits:geolocate {--limit=36 : Cantidad maxima de filas a resolver por corrida}';

    protected $description = 'Completa country_code/country_name de visitas pendientes via ip-api.com (gratis, con pausa entre llamados)';

    /**
     * El request web nunca espera una GeoIP externa: guarda el pais si
     * viene en el header CF-IPCountry y este comando completa el resto
     * por lotes, respetando el rate-limit gratuito (45 req/min).
     */
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $pending = SiteVisit::query()
            ->whereNull('country_code')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'ip']);

        if ($pending->isEmpty()) {
            $this->info('Sin visitas pendientes de geolocalizar.');

            return self::SUCCESS;
        }

        $resolved = 0;
        foreach ($pending as $visit) {
            $ip = (string) $visit->ip;

            // IPs privadas/reservadas (dev, red local): no salen a internet.
            if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $visit->update(['country_code' => 'OT', 'country_name' => 'Otros']);

                continue;
            }

            try {
                $data = Http::timeout(8)->get('http://ip-api.com/json/'.$ip.'?fields=status,country,countryCode')->json();
            } catch (\Throwable) {
                $data = null;
            }

            if (is_array($data) && ($data['status'] ?? null) === 'success' && ! empty($data['countryCode'])) {
                $visit->update([
                    'country_code' => strtoupper(substr((string) $data['countryCode'], 0, 2)),
                    'country_name' => substr((string) ($data['country'] ?? ''), 0, 80) ?: null,
                ]);
                $resolved++;
            }

            // ~40/hora: muy por debajo del limite gratuito.
            sleep(2);
        }

        $this->info("Geolocalizadas {$resolved} de {$pending->count()} visitas.");

        return self::SUCCESS;
    }
}

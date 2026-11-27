<?php

namespace App\Support;

use App\Models\SiteVisit;

/**
 * Estadisticas reales de visitas al sitio (tabla site_visits).
 *
 * Fuente unica para el modal web (DashboardController) y el endpoint
 * movil (Api\V1\ContactInquiriesController): misma estructura, mismos
 * porcentajes y promedios en ambos lados.
 */
class SiteVisitStats
{
    private const PALETTE = ['#ea580c', '#0c2a4d', '#f97316', '#1e40af', '#0d9488', '#7c3aed', '#db2777', '#65a30d'];

    private const KNOWN_NAMES = ['MX' => 'México', 'ES' => 'España', 'AR' => 'Argentina', 'CO' => 'Colombia', 'OT' => 'Otros'];

    public static function summary(): array
    {
        $today = today()->toDateString();
        $total = (int) SiteVisit::count();
        $todayCount = (int) SiteVisit::onDay($today)->count();
        $days = max(1, self::distinctDays());

        return [
            'total' => $total,
            'today' => $todayCount,
            'dailyAvg' => (int) round($total / $days),
            'ranges' => [
                'today' => self::range($today, $today, $total, $days),
                'd7' => self::range(today()->subDays(6)->toDateString(), $today, $total, $days),
                'd30' => self::range(today()->subDays(29)->toDateString(), $today, $total, $days),
            ],
        ];
    }

    public static function range(string $from, string $to, int $total, int $days): array
    {
        $rows = SiteVisit::query()
            ->selectRaw("COALESCE(NULLIF(country_code, ''), 'OT') as code, MAX(country_name) as name, COUNT(*) as visits")
            ->whereDate('visited_on', '>=', $from)
            ->whereDate('visited_on', '<=', $to)
            ->groupBy('code')
            ->orderByDesc('visits')
            ->get();

        $palette = self::PALETTE;
        $countries = [];
        foreach ($rows as $i => $row) {
            $code = strtoupper((string) ($row->code ?: 'OT'));
            $visits = (int) $row->visits;
            $countries[] = [
                'code' => $code,
                'name' => $row->name ?: (self::KNOWN_NAMES[$code] ?? $code),
                'flag' => self::flagFor($code),
                'color' => $palette[$i % count($palette)],
                'visits' => $visits,
                'pct' => $total > 0 ? (int) round($visits / $total * 100) : 0,
                'dailyAvg' => (int) round($visits / $days),
            ];
        }

        // Top 5 + resto agrupado en Otros para donut y lista compactos.
        if (count($countries) > 5) {
            $top = array_slice($countries, 0, 5);
            $restVisits = array_sum(array_column(array_slice($countries, 5), 'visits'));
            $top[] = [
                'code' => 'OT',
                'name' => 'Otros',
                'flag' => self::flagFor('OT'),
                'color' => end($palette),
                'visits' => $restVisits,
                'pct' => $total > 0 ? (int) round($restVisits / $total * 100) : 0,
                'dailyAvg' => (int) round($restVisits / $days),
            ];
            $countries = $top;
        }

        // Serie diaria con ceros (agrupa en PHP: cada driver devuelve la
        // fecha en su propio formato).
        $byDay = [];
        SiteVisit::query()
            ->whereDate('visited_on', '>=', $from)
            ->whereDate('visited_on', '<=', $to)
            ->pluck('visited_on')
            ->each(function ($value) use (&$byDay) {
                $key = $value instanceof \DateTimeInterface
                    ? $value->format('Y-m-d')
                    : substr((string) $value, 0, 10);
                $byDay[$key] = ($byDay[$key] ?? 0) + 1;
            });

        $series = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $series[] = [
                'label' => date('d/m', strtotime($cursor)),
                'visits' => (int) ($byDay[$cursor] ?? 0),
            ];
            $cursor = date('Y-m-d', strtotime($cursor.' +1 day'));
        }

        return ['countries' => $countries, 'days' => $series];
    }

    public static function flagFor(string $code): string
    {
        if ($code === 'OT') {
            return '🌍';
        }

        $flag = '';
        foreach (str_split($code) as $ch) {
            $flag .= mb_chr(127397 + ord($ch));
        }

        return $flag;
    }

    private static function distinctDays(): int
    {
        return SiteVisit::query()->pluck('visited_on')->map(
            fn ($value) => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : substr((string) $value, 0, 10)
        )->unique()->count();
    }
}

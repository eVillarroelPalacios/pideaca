<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Command;

/**
 * Proceso diario de membresías Hogar: emite facturas, envía recordatorios
 * de mora y suspende a los 15 días. En producción corre con cron diario
 * (Plesk: php artisan schedule:run o tarea directa a este comando).
 */
class MembershipDailyCommand extends Command
{
    protected $signature = 'membership:daily {--dry-run : solo informa, sin cambios}';

    protected $description = 'Emite facturas Hogar, recuerda moras y suspende a los 15 días';

    public function handle(MembershipService $service): int
    {
        if ($this->option('dry-run')) {
            $this->info('Modo simulación: no se aplica ningún cambio.');

            return self::SUCCESS;
        }

        $out = $service->runDaily();

        $this->info(sprintf(
            'Facturas emitidas: %d | Recordatorios: %d | Suspendidos: %d',
            $out['emitted'],
            $out['reminded'],
            $out['suspended']
        ));

        return self::SUCCESS;
    }
}

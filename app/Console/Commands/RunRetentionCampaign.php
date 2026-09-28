<?php

namespace App\Console\Commands;

use App\Services\RetentionCampaignService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunRetentionCampaign extends Command
{
    protected $signature = 'marketing:run-retention {--date= : Fecha de referencia (YYYY-MM-DD), por defecto hoy}';

    protected $description = 'Envia el mensaje de recuperacion a los clientes que cumplieron los dias de inactividad';

    public function handle(RetentionCampaignService $service): int
    {
        $fecha = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : today();

        $this->info('Motor de retencion ejecutado con fecha de referencia '.$fecha->toDateString());

        try {
            $resumen = $service->run($fecha);
        } catch (Throwable $e) {
            Log::error('Motor de retencion: fallo la corrida del '.$fecha->toDateString().': '.$e->getMessage());

            $this->error('La corrida fallo: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($resumen['rules'] === 0) {
            $this->warn('No hay reglas de retencion habilitadas.');

            return self::SUCCESS;
        }

        $this->table(
            ['Reglas', 'Enviados', 'Fallidos', 'Duplicados', 'Sin telefono', 'Fuera de ventana'],
            [[
                $resumen['rules'],
                $resumen['sent'],
                $resumen['failed'],
                $resumen['skipped_duplicate'],
                $resumen['skipped_no_phone'],
                $resumen['skipped_overdue'],
            ]]
        );

        if ($resumen['failed'] > 0) {
            Log::warning('Motor de retencion: '.$resumen['failed'].' envios fallaron el '.$fecha->toDateString().'.');
        }

        $this->info('Mensajes enviados: '.$resumen['sent'].'.');

        return self::SUCCESS;
    }
}

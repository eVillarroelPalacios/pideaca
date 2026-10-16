<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => ServiceType::CODE_EMERGENCY,
                'name' => 'Urgencias 24/7',
                'description' => 'Atencion de guardia inmediata con el profesional de turno.',
                'sort_order' => 1,
            ],
            [
                'code' => ServiceType::CODE_SCHEDULED,
                'name' => 'Cita programada',
                'description' => 'Reserva de un dia y horario para el servicio.',
                'sort_order' => 2,
            ],
            [
                'code' => ServiceType::CODE_QUOTE,
                'name' => 'Presupuesto',
                'description' => 'El cliente describe el trabajo y recibe propuestas de precio.',
                'sort_order' => 3,
            ],
        ];

        foreach ($types as $type) {
            ServiceType::firstOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}

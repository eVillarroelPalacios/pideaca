<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Catalogo de unidades de medida del modulo de inventario.
     * base_conversion_factor = factor para convertir a la unidad base (Unidad = 1).
     * is_integer_only = true cuando la unidad solo admite cantidades enteras.
     */
    public function run(): void
    {
        $now = now();
        $units = [
            ['name' => 'Unidad',       'symbol' => 'und', 'base_conversion_factor' => 1.0000, 'is_integer_only' => true],
            ['name' => 'Docena',       'symbol' => 'doc', 'base_conversion_factor' => 12.0000, 'is_integer_only' => true],
            ['name' => 'Media Docena', 'symbol' => 'mdoc', 'base_conversion_factor' => 6.0000, 'is_integer_only' => true],
            ['name' => 'Kg',           'symbol' => 'kg',  'base_conversion_factor' => 1.0000, 'is_integer_only' => false],
            ['name' => 'Gramo',        'symbol' => 'g',   'base_conversion_factor' => 0.0010, 'is_integer_only' => false],
            ['name' => 'Litro',        'symbol' => 'L',   'base_conversion_factor' => 1.0000, 'is_integer_only' => false],
        ];

        foreach ($units as $unit) {
            $unit['created_at'] = $now;
            $unit['updated_at'] = $now;
        }

        DB::table('unit_of_measures')->upsert(
            $units,
            ['name'],
            ['symbol', 'base_conversion_factor', 'is_integer_only', 'updated_at']
        );
    }
}

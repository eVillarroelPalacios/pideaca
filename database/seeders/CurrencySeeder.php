<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            'AR' => [
                'code' => 'ARS',
                'name' => 'Peso argentino',
                'symbol' => '$',
                'symbol_position' => 'before',
                'symbol_space' => true,
                'decimals' => 2,
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'locale' => 'es-AR',
                'is_active' => true,
            ],
            'ES' => [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'symbol_position' => 'after',
                'symbol_space' => true,
                'decimals' => 2,
                'decimal_separator' => ',',
                'thousand_separator' => '.',
                'locale' => 'es-ES',
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $isoCode => $data) {
            $country = Country::where('iso_code', $isoCode)->first();

            if (! $country) {
                continue;
            }

            Currency::updateOrCreate(
                ['country_id' => $country->id],
                $data
            );
        }
    }
}

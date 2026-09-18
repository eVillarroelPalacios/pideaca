<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CountriesAndRegionsSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['iso_code' => 'AR', 'name' => 'Argentina'],
            ['iso_code' => 'ES', 'name' => 'España'],
        ];

        foreach ($countries as $countryData) {
            $country = Country::updateOrCreate(
                ['iso_code' => $countryData['iso_code']],
                ['name' => $countryData['name'], 'is_active' => true]
            );

            if ($country->iso_code === 'AR') {
                $this->seedArgentina($country);
            } elseif ($country->iso_code === 'ES') {
                $this->seedSpain($country);
            }
        }
    }

    private function seedArgentina(Country $country): void
    {
        $provinces = [
            ['code' => 'C', 'name' => 'Ciudad Autónoma de Buenos Aires'],
            ['code' => 'B', 'name' => 'Buenos Aires'],
            ['code' => 'K', 'name' => 'Catamarca'],
            ['code' => 'H', 'name' => 'Chaco'],
            ['code' => 'U', 'name' => 'Chubut'],
            ['code' => 'X', 'name' => 'Córdoba'],
            ['code' => 'W', 'name' => 'Corrientes'],
            ['code' => 'E', 'name' => 'Entre Ríos'],
            ['code' => 'P', 'name' => 'Formosa'],
            ['code' => 'Y', 'name' => 'Jujuy'],
            ['code' => 'L', 'name' => 'La Pampa'],
            ['code' => 'F', 'name' => 'La Rioja'],
            ['code' => 'M', 'name' => 'Mendoza'],
            ['code' => 'N', 'name' => 'Misiones'],
            ['code' => 'Q', 'name' => 'Neuquén'],
            ['code' => 'R', 'name' => 'Río Negro'],
            ['code' => 'A', 'name' => 'Salta'],
            ['code' => 'J', 'name' => 'San Juan'],
            ['code' => 'D', 'name' => 'San Luis'],
            ['code' => 'Z', 'name' => 'Santa Cruz'],
            ['code' => 'S', 'name' => 'Santa Fe'],
            ['code' => 'G', 'name' => 'Santiago del Estero'],
            ['code' => 'V', 'name' => 'Tierra del Fuego'],
            ['code' => 'T', 'name' => 'Tucumán'],
        ];

        foreach ($provinces as $i => $province) {
            Region::updateOrCreate(
                ['country_id' => $country->id, 'code' => $province['code'], 'type' => 'province'],
                ['name' => $province['name'], 'parent_id' => null, 'sort_order' => $i + 1]
            );
        }
    }

    private function seedSpain(Country $country): void
    {
        $communities = [
            ['code' => 'AN', 'name' => 'Andalucía'],
            ['code' => 'AR', 'name' => 'Aragón'],
            ['code' => 'AS', 'name' => 'Principado de Asturias'],
            ['code' => 'IB', 'name' => 'Islas Baleares'],
            ['code' => 'CN', 'name' => 'Canarias'],
            ['code' => 'CB', 'name' => 'Cantabria'],
            ['code' => 'CL', 'name' => 'Castilla y León'],
            ['code' => 'CM', 'name' => 'Castilla-La Mancha'],
            ['code' => 'CT', 'name' => 'Cataluña'],
            ['code' => 'VC', 'name' => 'Comunidad Valenciana'],
            ['code' => 'EX', 'name' => 'Extremadura'],
            ['code' => 'GA', 'name' => 'Galicia'],
            ['code' => 'RI', 'name' => 'La Rioja'],
            ['code' => 'MD', 'name' => 'Comunidad de Madrid'],
            ['code' => 'MC', 'name' => 'Región de Murcia'],
            ['code' => 'NC', 'name' => 'Comunidad Foral de Navarra'],
            ['code' => 'PV', 'name' => 'País Vasco'],
            ['code' => 'CE', 'name' => 'Ceuta'],
            ['code' => 'ML', 'name' => 'Melilla'],
        ];

        $communityIds = [];
        foreach ($communities as $i => $community) {
            $region = Region::updateOrCreate(
                ['country_id' => $country->id, 'code' => $community['code'], 'type' => 'region'],
                ['name' => $community['name'], 'parent_id' => null, 'sort_order' => $i + 1]
            );
            $communityIds[$community['code']] = $region->id;
        }

        $provinces = [
            ['code' => '01', 'name' => 'Álava', 'community' => 'PV'],
            ['code' => '02', 'name' => 'Albacete', 'community' => 'CM'],
            ['code' => '03', 'name' => 'Alicante', 'community' => 'VC'],
            ['code' => '04', 'name' => 'Almería', 'community' => 'AN'],
            ['code' => '05', 'name' => 'Ávila', 'community' => 'CL'],
            ['code' => '06', 'name' => 'Badajoz', 'community' => 'EX'],
            ['code' => '07', 'name' => 'Islas Baleares', 'community' => 'IB'],
            ['code' => '08', 'name' => 'Barcelona', 'community' => 'CT'],
            ['code' => '09', 'name' => 'Burgos', 'community' => 'CL'],
            ['code' => '10', 'name' => 'Cáceres', 'community' => 'EX'],
            ['code' => '11', 'name' => 'Cádiz', 'community' => 'AN'],
            ['code' => '12', 'name' => 'Castellón', 'community' => 'VC'],
            ['code' => '13', 'name' => 'Ciudad Real', 'community' => 'CM'],
            ['code' => '14', 'name' => 'Córdoba', 'community' => 'AN'],
            ['code' => '15', 'name' => 'A Coruña', 'community' => 'GA'],
            ['code' => '16', 'name' => 'Cuenca', 'community' => 'CM'],
            ['code' => '17', 'name' => 'Girona', 'community' => 'CT'],
            ['code' => '18', 'name' => 'Granada', 'community' => 'AN'],
            ['code' => '19', 'name' => 'Guadalajara', 'community' => 'CM'],
            ['code' => '20', 'name' => 'Gipuzkoa', 'community' => 'PV'],
            ['code' => '21', 'name' => 'Huelva', 'community' => 'AN'],
            ['code' => '22', 'name' => 'Huesca', 'community' => 'AR'],
            ['code' => '23', 'name' => 'Jaén', 'community' => 'AN'],
            ['code' => '24', 'name' => 'León', 'community' => 'CL'],
            ['code' => '25', 'name' => 'Lleida', 'community' => 'CT'],
            ['code' => '26', 'name' => 'La Rioja', 'community' => 'RI'],
            ['code' => '27', 'name' => 'Lugo', 'community' => 'GA'],
            ['code' => '28', 'name' => 'Madrid', 'community' => 'MD'],
            ['code' => '29', 'name' => 'Málaga', 'community' => 'AN'],
            ['code' => '30', 'name' => 'Murcia', 'community' => 'MC'],
            ['code' => '31', 'name' => 'Navarra', 'community' => 'NC'],
            ['code' => '32', 'name' => 'Ourense', 'community' => 'GA'],
            ['code' => '33', 'name' => 'Asturias', 'community' => 'AS'],
            ['code' => '34', 'name' => 'Palencia', 'community' => 'CL'],
            ['code' => '35', 'name' => 'Las Palmas', 'community' => 'CN'],
            ['code' => '36', 'name' => 'Pontevedra', 'community' => 'GA'],
            ['code' => '37', 'name' => 'Salamanca', 'community' => 'CL'],
            ['code' => '38', 'name' => 'Santa Cruz de Tenerife', 'community' => 'CN'],
            ['code' => '39', 'name' => 'Cantabria', 'community' => 'CB'],
            ['code' => '40', 'name' => 'Segovia', 'community' => 'CL'],
            ['code' => '41', 'name' => 'Sevilla', 'community' => 'AN'],
            ['code' => '42', 'name' => 'Soria', 'community' => 'CL'],
            ['code' => '43', 'name' => 'Tarragona', 'community' => 'CT'],
            ['code' => '44', 'name' => 'Teruel', 'community' => 'AR'],
            ['code' => '45', 'name' => 'Toledo', 'community' => 'CM'],
            ['code' => '46', 'name' => 'Valencia', 'community' => 'VC'],
            ['code' => '47', 'name' => 'Valladolid', 'community' => 'CL'],
            ['code' => '48', 'name' => 'Bizkaia', 'community' => 'PV'],
            ['code' => '49', 'name' => 'Zamora', 'community' => 'CL'],
            ['code' => '50', 'name' => 'Zaragoza', 'community' => 'AR'],
        ];

        foreach ($provinces as $i => $province) {
            Region::updateOrCreate(
                ['country_id' => $country->id, 'code' => $province['code'], 'type' => 'province'],
                [
                    'name' => $province['name'],
                    'parent_id' => $communityIds[$province['community']],
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
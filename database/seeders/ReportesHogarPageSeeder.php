<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportesHogarPageSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::whereIn('description', ['Hogar', 'Servicios del Hogar & Bienestar'])->first();

        if (! $module) {
            $module = Module::firstOrCreate(
                ['description' => 'Hogar'],
                ['icon' => null]
            );
        }

        $page = Page::where('url', 'reportes-hogar')->first();

        if (! $page) {
            $page = Page::where('description', 'Reportes Hogar')->first();
        }

        if ($page) {
            $page->update([
                'description' => 'Reportes Hogar',
                'url' => 'reportes-hogar',
                'module_id' => $module->id,
            ]);
        } else {
            $page = Page::create([
                'description' => 'Reportes Hogar',
                'url' => 'reportes-hogar',
                'module_id' => $module->id,
            ]);
        }

        foreach (['Prestador', 'Cliente'] as $tipoDescripcion) {
            $type = TypeUser::where('description', $tipoDescripcion)->first();

            if (! $type) {
                continue;
            }

            $type->assignedPages()->syncWithoutDetaching([$page->id]);
            
            $users = User::where('type_user_id', $type->id)->get();
            foreach ($users as $user) {
                $user->pages()->syncWithoutDetaching([$page->id]);
            }
        }
    }
}

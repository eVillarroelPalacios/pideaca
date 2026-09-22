<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Page;
use App\Models\User;

class UsuariosPageSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['description' => 'Administrar'],
            ['icon' => null]
        );

        $page = Page::firstOrCreate(
            ['description' => 'Usuarios'],
            [
                'url' => 'usuarios',
                'module_id' => $module->id,
            ]
        );

        $user = User::find(1);

        if ($user) {
            $user->pages()->syncWithoutDetaching([$page->id]);
        }
    }
}

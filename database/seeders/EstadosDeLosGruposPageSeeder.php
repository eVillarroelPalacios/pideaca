<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class EstadosDeLosGruposPageSeeder extends Seeder
{
    /**
     * Crea la pagina "Estado de los grupos", la deja en la plantilla del tipo
     * Admin (para que un Admin nuevo tambien la reciba) y se la asigna al
     * usuario 1 para que aparezca en su menu y pueda entrar al circuito.
     */
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['description' => 'Administrar'],
            ['icon' => null]
        );

        $page = Page::firstOrCreate(
            ['url' => 'estados-grupos'],
            [
                'description' => 'Estado de los grupos',
                'module_id' => $module->id,
            ]
        );

        $admin = TypeUser::where('description', 'Admin')->first();

        if ($admin) {
            $admin->assignedPages()->syncWithoutDetaching([$page->id]);
        }

        $user = User::find(1);

        if ($user) {
            $user->pages()->syncWithoutDetaching([$page->id]);
        }
    }
}

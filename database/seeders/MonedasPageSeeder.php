<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonedasPageSeeder extends Seeder
{
    /**
     * Vincula la pagina administrativa "Tipo de monedas" con la url que usa
     * el dashboard (tipo-monedas), la deja en la plantilla del tipo Admin
     * (para que un Admin nuevo tambien la reciba) y se la asigna al usuario 1
     * para que aparezca en su menu. Si la pagina ya existe creada desde el
     * panel (con o sin url), se adopta en vez de duplicarla.
     */
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['description' => 'Administrar'],
            ['icon' => null]
        );

        $page = Page::where('url', 'tipo-monedas')->first();

        if (! $page) {
            $page = Page::where('description', 'Tipo de monedas')->first();
        }

        if ($page) {
            $page->update([
                'url' => 'tipo-monedas',
                'module_id' => $module->id,
            ]);
        } else {
            $page = Page::create([
                'description' => 'Tipo de monedas',
                'url' => 'tipo-monedas',
                'module_id' => $module->id,
            ]);
        }

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

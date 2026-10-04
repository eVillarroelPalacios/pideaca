<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class UnidadesMedidaPageSeeder extends Seeder
{
    /**
     * Vincula la pagina administrativa "Unid. Medidas" con la url que usa el
     * dashboard (unidades-medida), la deja en la plantilla del tipo Admin (para
     * que un Admin nuevo tambien la reciba) y se la asigna al usuario 1 para
     * que aparezca en su menu. Si la pagina ya existe creada desde el panel
     * (con o sin url), se adopta en vez de duplicarla.
     */
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['description' => 'Administrar'],
            ['icon' => null]
        );

        $page = Page::where('url', 'unidades-medida')->first();

        if (! $page) {
            $page = Page::where('description', 'Unid. Medidas')
                ->orWhere('description', 'Unidades de medida')
                ->first();
        }

        if ($page) {
            $page->update([
                'url' => 'unidades-medida',
                'module_id' => $module->id,
            ]);
        } else {
            $page = Page::create([
                'description' => 'Unid. Medidas',
                'url' => 'unidades-medida',
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

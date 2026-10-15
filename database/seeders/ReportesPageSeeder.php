<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportesPageSeeder extends Seeder
{
    /**
     * Desbloquea la pagina "Reportes" del modulo Comercio & Gastronomia: le
     * asigna la url del dashboard (reportes), la deja en la plantilla de los
     * tipos Prestador y Cliente y se la propaga a los usuarios de esos tipos.
     * Si la pagina ya existe creada desde el panel (con o sin url), se adopta
     * en vez de duplicarla.
     */
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['description' => 'Comercio & Gastronomía'],
            ['icon' => null]
        );

        $page = Page::where('url', 'reportes')->first();

        if (! $page) {
            $page = Page::where('description', 'Reportes')->first();
        }

        if ($page) {
            $page->update([
                'url' => 'reportes',
                'module_id' => $module->id,
            ]);
        } else {
            $page = Page::create([
                'description' => 'Reportes',
                'url' => 'reportes',
                'module_id' => $module->id,
            ]);
        }

        foreach (['Prestador', 'Cliente'] as $tipoDescripcion) {
            $type = TypeUser::where('description', $tipoDescripcion)->first();

            if (! $type) {
                $this->command?->warn("No se encontró el tipo de usuario «{$tipoDescripcion}»: se omite su pagina Reportes.");

                continue;
            }

            $type->assignedPages()->syncWithoutDetaching([$page->id]);
            $this->syncUsersOfType($type);
        }
    }

    /**
     * Los usuarios existentes del tipo quedan con la misma plantilla que recibe
     * un usuario nuevo al registrarse. Solo agrega: nunca quita paginas que un
     * administrador haya asignado a mano.
     */
    private function syncUsersOfType(TypeUser $type): void
    {
        $templateIds = $type->assignedPages()->pluck('pages.id')->all();

        User::where('type_user_id', $type->id)
            ->chunkById(200, function ($users) use ($templateIds) {
                foreach ($users as $user) {
                    $user->pages()->syncWithoutDetaching($templateIds);
                }
            });
    }
}

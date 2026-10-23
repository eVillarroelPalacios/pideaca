<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Activa la pagina unificada "Ordenes y Gestion" del prestador.
 *
 * La pagina se habia creado desde el panel todavia sin url (por eso el
 * menu la mostraba como "pronto"): aca se le asigna la url que atiende
 * OrdenesGestionController, se crea si no existe y se la deja en la
 * plantilla del tipo Prestador (igual que hace el registro).
 *
 * Es idempotente y no quita paginas: las paginas viejas
 * ("Ordenes de Servicio" / "Gestion") siguen existiendo para quien ya
 * las tenga asignadas a mano.
 */
class OrdenesGestionPageSeeder extends Seeder
{
    private const DESCRIPTION = 'Órdenes y Gestión';

    private const URL = 'ordenes-gestion';

    public function run(): void
    {
        DB::transaction(function () {
            $page = Page::where('description', self::DESCRIPTION)
                ->orWhere('url', self::URL)
                ->first();

            if (! $page) {
                $page = Page::create([
                    'description' => self::DESCRIPTION,
                    'url' => self::URL,
                    'module_id' => $this->resolveModuleId(),
                ]);
            }

            $dirty = false;

            if ($page->url !== self::URL) {
                $page->url = self::URL;
                $dirty = true;
            }

            if (! $page->module_id) {
                $page->module_id = $this->resolveModuleId();
                $dirty = true;
            }

            if ($dirty) {
                $page->save();
            }

            $type = TypeUser::where('description', 'Prestador')->first();

            if (! $type) {
                $this->command?->warn('No se encontro el tipo de usuario «Prestador»: la pagina queda creada sin asignar.');

                return;
            }

            $type->assignedPages()->syncWithoutDetaching([$page->id]);

            // Los usuarios existentes del tipo quedan con la misma plantilla
            // que recibe un usuario nuevo al registrarse.
            User::where('type_user_id', $type->id)
                ->chunkById(200, function ($users) use ($page) {
                    foreach ($users as $user) {
                        $user->pages()->syncWithoutDetaching([$page->id]);
                    }
                });
        });
    }

    /**
     * El modulo del servicio: se usa el mismo nombre que ya tienen las
     * paginas del rubro (varia un poco entre entornos).
     */
    private function resolveModuleId(): int
    {
        $module = Module::whereIn('description', ['Hogar', 'Servicios del Hogar & Bienestar'])
            ->orderBy('id')
            ->first();

        return ($module ?: Module::firstOrCreate(
            ['description' => 'Hogar'],
            ['icon' => null]
        ))->id;
    }
}

<?php

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El menu del dashboard se arma con las paginas que tiene cada usuario, asi
     * que un circuito sin pagina es un circuito invisible: por eso finanzas,
     * retencion y suscripciones no aparecian en ningun lado.
     *
     * Cada pagina se registra tambien en page_type_user, que es la plantilla que
     * usa el registro, y se propaga a los usuarios que ya existen.
     */
    public function up(): void
    {
        $comercio = Module::where('description', 'Comercio & Gastronomía')->first()
            ?? Module::where('description', 'Comercio & Gastron')->first();

        if (! $comercio) {
            return;
        }

        $paginas = [
            'Prestador' => [
                ['description' => 'Salud financiera', 'url' => 'finanzas'],
                ['description' => 'Retención', 'url' => 'retencion'],
                ['description' => 'Suscripciones', 'url' => 'suscripciones'],
            ],
            'Cliente' => [
                ['description' => 'Mis Suscripciones', 'url' => 'mis-suscripciones'],
            ],
        ];

        foreach ($paginas as $tipoDescripcion => $definiciones) {
            $tipo = TypeUser::where('description', $tipoDescripcion)->first();

            if (! $tipo) {
                continue;
            }

            $pageIds = [];

            foreach ($definiciones as $definicion) {
                // La description es la clave natural: si la pagina ya existe desde
                // el panel, no se le pisa la url ni el modulo.
                $page = Page::firstOrCreate(
                    ['description' => $definicion['description']],
                    ['url' => $definicion['url'], 'module_id' => $comercio->id]
                );

                $pageIds[] = $page->id;
            }

            $tipo->assignedPages()->syncWithoutDetaching($pageIds);

            User::where('type_user_id', $tipo->id)
                ->chunkById(200, function ($users) use ($pageIds) {
                    foreach ($users as $user) {
                        $user->pages()->syncWithoutDetaching($pageIds);
                    }
                });
        }
    }

    public function down(): void
    {
        $urls = ['finanzas', 'retencion', 'suscripciones', 'mis-suscripciones'];
        $ids = DB::table('pages')->whereIn('url', $urls)->pluck('id');

        DB::table('page_type_user')->whereIn('page_id', $ids)->delete();
        DB::table('page_user')->whereIn('page_id', $ids)->delete();
        DB::table('pages')->whereIn('id', $ids)->delete();
    }
};

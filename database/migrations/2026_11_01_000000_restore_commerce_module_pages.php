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
     * Restaura el circuito de paginas del comercio que quedo sin registrar.
     *
     * La migracion 2026_09_28_080000_create_commerce_module_pages buscaba un
     * modulo llamado "Comercio & Gastronomia", pero la base productiva usa
     * "Mercado"; al no encontrarlo salia sin crear nada, asi que Salud
     * financiera, Retencion, Suscripciones y Mis Suscripciones quedaban fuera
     * del menu (el menu se arma con page_user / page_type_user).
     *
     * Esta migracion es idempotente: si las paginas ya existen las reutiliza y
     * solo completa lo que falte (modulo o url), y si no, las crea.
     */
    public function up(): void
    {
        $comercio = Module::where('description', 'Mercado')->first()
            ?? Module::where('description', 'Comercio & Gastronomía')->first()
            ?? Module::where('description', 'Comercio & Gastron')->first()
            ?? Module::where('description', 'like', '%Mercado%')->first();

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
                $page = Page::where('url', $definicion['url'])->first()
                    ?? Page::where('description', $definicion['description'])->first();

                if ($page) {
                    // La pagina ya existia (por ejemplo, creada desde el panel
                    // con otro modulo): se repara sin pisar contenido.
                    $cambios = [];

                    if (! $page->module_id) {
                        $cambios['module_id'] = $comercio->id;
                    }

                    if (! $page->url) {
                        $cambios['url'] = $definicion['url'];
                    }

                    if ($cambios) {
                        $page->update($cambios);
                    }
                } else {
                    $page = Page::create([
                        'description' => $definicion['description'],
                        'url' => $definicion['url'],
                        'module_id' => $comercio->id,
                    ]);
                }

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

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('page_type_user')->whereIn('page_id', $ids)->delete();
        DB::table('page_user')->whereIn('page_id', $ids)->delete();
        DB::table('pages')->whereIn('id', $ids)->delete();
    }
};

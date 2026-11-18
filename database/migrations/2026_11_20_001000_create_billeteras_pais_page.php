<?php

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Página "Billeteras por país" en el módulo Administrar (catálogo de tipos
 * de billetera por país). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $modulo = Module::where('description', 'Administrar')->first();

        if (! $modulo) {
            return;
        }

        $page = Page::where('url', 'billeteras-pais')->first();

        if (! $page) {
            $page = Page::create([
                'description' => 'Billeteras por país',
                'url' => 'billeteras-pais',
                'module_id' => $modulo->id,
            ]);
        }

        $tipos = TypeUser::whereRaw('LOWER(description) IN (?, ?)', ['admin', 'administrador'])->get();

        foreach ($tipos as $tipo) {
            $tipo->assignedPages()->syncWithoutDetaching([$page->id]);
        }

        User::whereIn('type_user_id', $tipos->pluck('id')->all())
            ->chunkById(200, function ($users) use ($page) {
                foreach ($users as $user) {
                    $user->pages()->syncWithoutDetaching([$page->id]);
                }
            });
    }

    public function down(): void
    {
        $ids = DB::table('pages')->where('url', 'billeteras-pais')->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('page_type_user')->whereIn('page_id', $ids)->delete();
        DB::table('page_user')->whereIn('page_id', $ids)->delete();
        DB::table('pages')->whereIn('id', $ids)->delete();
    }
};

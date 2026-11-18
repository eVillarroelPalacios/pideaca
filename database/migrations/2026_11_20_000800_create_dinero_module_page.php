<?php

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Módulo D (Dinero): una sola página "Dinero" (url 'dinero') con pestañas
 * internas (comisiones, matriz, liquidaciones, efectivo, conciliación,
 * suscripciones). Se asigna a los administradores actuales y al tipo Admin.
 * Idempotente: reutiliza lo que ya exista. No modifica nada existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $modulo = Module::where('description', 'Dinero')->first();

        if (! $modulo) {
            $modulo = Module::create(['description' => 'Dinero']);
        }

        $page = Page::where('url', 'dinero')->first();

        if (! $page) {
            $page = Page::create([
                'description' => 'Dinero',
                'url' => 'dinero',
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
        $ids = DB::table('pages')->where('url', 'dinero')->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('page_type_user')->whereIn('page_id', $ids)->delete();
        DB::table('page_user')->whereIn('page_id', $ids)->delete();
        DB::table('pages')->whereIn('id', $ids)->delete();
    }
};

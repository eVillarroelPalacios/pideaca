<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->after('id')
                ->constrained('modules')->nullOnDelete();
        });

        // Backfill del vinculo grupo<->module. Los modulos se pueden renombrar
        // (p. ej. "Mercado"/"Hogar") sin romper los listados que filtran por
        // modulo; el id es estable aunque cambie la etiqueta.
        $mercado = DB::table('modules')->where('description', 'Mercado')->value('id')
            ?? DB::table('modules')->where('description', 'Comercio & Gastronomía')->value('id');

        $hogar = DB::table('modules')->where('description', 'Hogar')->value('id')
            ?? DB::table('modules')->where('description', 'Servicios del Hogar & Bienestar')->value('id');

        if ($mercado) {
            DB::table('groups')->where('description', 'Comercio & Gastronomía')->update(['module_id' => $mercado]);
        }

        if ($hogar) {
            DB::table('groups')->where('description', 'Servicios del Hogar & Bienestar')->update(['module_id' => $hogar]);
        }
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_id');
        });
    }
};

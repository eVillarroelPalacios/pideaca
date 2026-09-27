<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo de estados para controlar si un grupo esta activo o no.
     * La tabla se llama en ingles (group_statuses) y guarda la
     * descripcion en espanol, igual que user_statuses.
     */
    public function up(): void
    {
        Schema::create('group_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('description')->unique();
            $table->timestamps();
        });

        DB::table('group_statuses')->insert([
            ['description' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
            ['description' => 'Desactivo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('group_status_id')
                ->nullable()
                ->constrained('group_statuses')
                ->nullOnDelete();
        });

        // Los grupos que ya existen quedan marcados como activos.
        $activoId = DB::table('group_statuses')->where('description', 'Activo')->value('id');

        DB::table('groups')->whereNull('group_status_id')->update(['group_status_id' => $activoId]);
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['group_status_id']);
            $table->dropColumn(['group_status_id']);
        });

        Schema::dropIfExists('group_statuses');
    }
};

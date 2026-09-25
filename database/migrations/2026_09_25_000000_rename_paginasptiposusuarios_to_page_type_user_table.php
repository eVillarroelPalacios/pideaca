<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('paginasptiposusuarios', 'page_type_user');

        $this->renameSequence('paginasptiposusuarios_id_seq', 'page_type_user_id_seq');
    }

    public function down(): void
    {
        $this->renameSequence('page_type_user_id_seq', 'paginasptiposusuarios_id_seq');

        Schema::rename('page_type_user', 'paginasptiposusuarios');
    }

    private function renameSequence(string $from, string $to): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $exists = DB::selectOne('SELECT to_regclass(?) AS seq', ['public.'.$from]);

        if ($exists && $exists->seq !== null) {
            DB::statement(sprintf('ALTER SEQUENCE public.%s RENAME TO %s', $from, $to));
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Move the user status catalogue to Spanish and make sure the
     * advertising statuses (Activo / Prueba) exist.
     *
     * Advertising is only enabled for Prestador users whose status is
     * Activo or Prueba, so those two values must be present.
     */
    public function up(): void
    {
        DB::table('user_statuses')->whereRaw('LOWER(status) = ?', ['active'])->update(['status' => 'Activo']);
        DB::table('user_statuses')->whereRaw('LOWER(status) = ?', ['inactive'])->update(['status' => 'Inactivo']);

        DB::table('user_statuses')->insertOrIgnore(['status' => 'Prueba']);
    }

    public function down(): void
    {
        DB::table('user_statuses')->whereRaw('LOWER(status) = ?', ['activo'])->update(['status' => 'Active']);
        DB::table('user_statuses')->whereRaw('LOWER(status) = ?', ['inactivo'])->update(['status' => 'Inactive']);
    }
};

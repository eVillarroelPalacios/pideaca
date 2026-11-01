<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('providers', 'trusted_drivers_enabled')) {
            Schema::table('providers', function (Blueprint $table) {
                // El comercio activa/desactiva explicitamente el despacho
                // cerrado a su lista de repartidores de confianza.
                $table->boolean('trusted_drivers_enabled')->default(false);
            });
        }

        if (! Schema::hasColumn('orders', 'released_to_pool_at')) {
            Schema::table('orders', function (Blueprint $table) {
                // Solo se completa con una accion manual del comercio
                // ("Enviar al pool general"). No existe ningun timer que lo
                // haga de forma automatica.
                $table->timestamp('released_to_pool_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'released_to_pool_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('released_to_pool_at');
            });
        }

        if (Schema::hasColumn('providers', 'trusted_drivers_enabled')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('trusted_drivers_enabled');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direccion donde el cliente necesita la urgencia: se completa con el boton
 * "Usar mi ubicacion actual" (geolocalizacion + reverse geocoding) o se
 * precarga desde la direccion principal del perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->text('address')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};

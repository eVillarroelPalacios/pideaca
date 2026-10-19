<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de acceso de la cita programada: direccion estructurada (calle y
 * numero, piso/depto, barrio/zona) y el canal elegido para el recordatorio
 * (whatsapp o email). La columna `address` sigue existiendo para la urgencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('address_street', 150)->nullable()->after('address');
            $table->string('address_unit', 50)->nullable()->after('address_street');
            $table->string('address_neighborhood', 100)->nullable()->after('address_unit');
            $table->string('reminder_channel', 20)->nullable()->after('address_neighborhood');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['address_street', 'address_unit', 'address_neighborhood', 'reminder_channel']);
        });
    }
};

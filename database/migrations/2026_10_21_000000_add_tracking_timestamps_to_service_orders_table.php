<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marcas de seguimiento de la orden: cuando el prestador acepta el trabajo
 * (accepted_at) y cuando lo inicia / sale a atenderlo (started_at). Con esto
 * el prestador puede ver la linea de tiempo real de cada solicitud.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('scheduled_at');
            $table->timestamp('started_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['accepted_at', 'started_at']);
        });
    }
};

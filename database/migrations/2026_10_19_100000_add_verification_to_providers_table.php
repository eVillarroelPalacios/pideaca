<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encabezado del prestador en la ventana de urgencias: la insignia
 * "Prestador Verificado" y la tarifa base de la visita/urgencia que se
 * muestra antes de pedir el servicio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->decimal('base_visit_fee', 10, 2)->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'base_visit_fee']);
        });
    }
};

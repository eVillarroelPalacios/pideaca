<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto dinamico del presupuesto: rubro detectado, atajos rapidos elegidos
 * y respuestas a las preguntas contextuales. Se guarda como JSON para poder
 * evolucionar la configuracion por rubro sin cambiar el esquema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->json('quote_context')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('quote_context');
        });
    }
};

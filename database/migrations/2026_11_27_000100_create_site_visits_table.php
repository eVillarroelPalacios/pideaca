<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visitas unicas al sitio (una fila por IP y dia). Sin FK ni borrados:
     * es historial de trafico para el modal de estadisticas.
     */
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->char('ip_hash', 64);
            $table->date('visited_on');
            $table->string('ip', 45)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('country_name', 80)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->unique(['ip_hash', 'visited_on']);
            $table->index('visited_on');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};

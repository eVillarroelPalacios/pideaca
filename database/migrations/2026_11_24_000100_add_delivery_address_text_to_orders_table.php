<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direccion de entrega alternativa en texto libre (checkout con
     * "Enviar a una direccion diferente"). Cuando viene con contenido se
     * usa en lugar de address_id. Nullable: no toca pedidos existentes.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_address_text')) {
                $table->string('delivery_address_text', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_address_text');
        });
    }
};

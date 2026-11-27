<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca los cobros confirmados vía split de Marketplace (el dinero fue
     * directo al vendedor y la comisión se descontó sola). Sin esto no se
     * distingue un cobro recaudado por PideAca de uno con split.
     */
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->boolean('split_marketplace')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropColumn('split_marketplace');
        });
    }
};

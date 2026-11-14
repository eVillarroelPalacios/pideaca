<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Motor de tarifas por comercio.
     *
     * - global:   usa FAST_DELIVERY_FEE (comportamiento historico).
     * - flat:     el comercio fija su propio monto.
     * - distance: base + (km * precio_por_km), con piso y techo.
     *
     * Todos los defaults replican el comportamiento actual, de modo que
     * ningun comercio existente cambia de precio hasta que lo configure.
     */
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            if (! Schema::hasColumn('providers', 'delivery_fee_mode')) {
                $table->string('delivery_fee_mode', 20)->default('global');
            }
            if (! Schema::hasColumn('providers', 'delivery_fee_flat')) {
                $table->decimal('delivery_fee_flat', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('providers', 'delivery_fee_base')) {
                $table->decimal('delivery_fee_base', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('providers', 'delivery_fee_per_km')) {
                $table->decimal('delivery_fee_per_km', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('providers', 'delivery_fee_min')) {
                $table->decimal('delivery_fee_min', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('providers', 'delivery_fee_max')) {
                $table->decimal('delivery_fee_max', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_fee_mode',
                'delivery_fee_flat',
                'delivery_fee_base',
                'delivery_fee_per_km',
                'delivery_fee_min',
                'delivery_fee_max',
            ]);
        });
    }
};

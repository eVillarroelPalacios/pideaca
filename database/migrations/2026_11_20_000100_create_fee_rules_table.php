<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reglas de comisión por módulo/país/moneda. La regla aplicada se copia al
// pago (histórico inmutable): cambiar el % no recalcula pagos viejos.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_rules', function (Blueprint $table) {
            $table->id();
            $table->string('module', 20); // 'mercado' | 'hogar'
            $table->string('country', 2)->nullable(); // 'AR' | 'ES' | null = todos
            $table->string('currency', 3)->nullable(); // 'ARS' | 'EUR' | null = todas
            $table->decimal('percent', 5, 2)->default(0); // ej. 2.00
            $table->decimal('fixed_fee', 12, 2)->default(0);
            $table->decimal('min_fee', 12, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->timestamps();

            $table->index(['module', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_rules');
    }
};

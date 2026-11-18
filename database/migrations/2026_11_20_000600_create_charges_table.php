<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cobros manuales del motor (Fase 1). Referencia polimórfica SIN FK dura:
// vale para market orders y service orders sin tocar esas tablas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->string('module', 20); // mercado|hogar
            $table->string('order_type', 30); // order|service_order
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ARS');
            $table->string('method', 20); // transferencia|efectivo
            $table->string('status', 30)->default('pending_verification'); // pending_verification|verified|cancelled
            $table->string('transaction_reference', 255)->nullable();
            $table->unsignedBigInteger('fee_rule_id')->nullable();
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->decimal('fee_fixed', 12, 2)->default(0);
            $table->decimal('fee_min', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_type', 'order_id']);
            $table->index(['module', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};

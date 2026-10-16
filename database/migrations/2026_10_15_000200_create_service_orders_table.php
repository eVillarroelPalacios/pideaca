<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            // El tipo de servicio es un SubGrupo del catalogo general (grupo
            // "Servicios del Hogar & Bienestar"). La orden nace en un pool: el
            // comercio se asigna recien cuando un prestador la acepta.
            $table->foreignId('sub_group_id')->constrained('sub_groups')->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('order_type', 20);
            $table->string('status', 30)->default('REQUESTED');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('visit_fee', 10, 2)->default(0);
            $table->decimal('additional_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('escrow_status', 20)->default('HOLDING');
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index('sub_group_id');
            $table->index('order_type');
            $table->index('escrow_status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};

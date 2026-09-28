<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: es un registro contable, no debe evaporarse
            // porque se borre una suscripcion.
            $table->foreignId('customer_subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('gateway', 50)->default('manual');
            $table->string('gateway_payment_id')->nullable();
            $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'REFUNDED', 'CANCELLED']);
            $table->unsignedTinyInteger('attempt')->default(1);

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('ARS');

            $table->date('period_start');
            $table->date('period_end');
            $table->dateTime('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_subscription_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index('order_id');
            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};

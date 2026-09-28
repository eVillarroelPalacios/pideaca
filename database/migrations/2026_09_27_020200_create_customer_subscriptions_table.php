<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->enum('status', ['ACTIVE', 'PAUSED', 'CANCELLED', 'PAYMENT_FAILED']);
            $table->dateTime('next_delivery_date');
            $table->foreignId('delivery_address_id')->constrained('addresses')->restrictOnDelete();
            $table->string('payment_method');
            $table->timestamps();

            $table->index('user_id');
            $table->index('provider_id');
            $table->index('subscription_plan_id');
            $table->index('delivery_address_id');
            $table->index(['user_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index('next_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_subscriptions');
    }
};

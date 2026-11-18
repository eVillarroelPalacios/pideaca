<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Planes de suscripción Hogar (configurables) + facturas mensuales con
// mora: avisos, suspensión día 15 y reactivación al pagar (deuda congelada).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('module', 20)->default('hogar');
            $table->string('currency', 3)->default('ARS');
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('billing_days')->default(30);
            $table->unsignedInteger('grace_days')->default(15);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('membership_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_name', 100)->nullable(); // foto del plan
            $table->decimal('amount', 12, 2)->default(0); // foto del precio
            $table->string('currency', 3)->default('ARS');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending'); // pending|paid|overdue|cancelled
            $table->unsignedInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_invoices');
        Schema::dropIfExists('membership_plans');
    }
};

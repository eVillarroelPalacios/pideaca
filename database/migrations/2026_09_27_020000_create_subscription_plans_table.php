<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('frequency', ['DAILY', 'WEEKLY', 'BIWEEKLY', 'MONTHLY']);
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('provider_id');
            $table->index(['provider_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

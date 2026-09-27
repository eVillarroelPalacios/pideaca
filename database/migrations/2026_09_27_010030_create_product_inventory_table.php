<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('reserved_stock', 10, 3)->default(0);
            $table->boolean('allow_negative_stock')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'provider_id']);
            $table->index('provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventory');
    }
};

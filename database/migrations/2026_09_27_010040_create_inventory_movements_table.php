<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('movement_type', ['IN', 'OUT', 'ADJUSTMENT', 'SALE', 'CANCELLED_SALE']);
            $table->decimal('quantity', 10, 3);
            $table->foreignId('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->decimal('unit_conversion_factor', 10, 4);
            $table->decimal('quantity_in_base_unit', 10, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('provider_id');
            $table->index('order_id');
            $table->index('unit_of_measure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};

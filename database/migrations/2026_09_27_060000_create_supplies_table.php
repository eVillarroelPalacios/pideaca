<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            // Costo de una unidad de medida (por kg, litro o unidad).
            $table->decimal('cost_per_unit', 10, 2);
            $table->timestamps();

            $table->unique(['provider_id', 'name']);
            $table->index('unit_of_measure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};

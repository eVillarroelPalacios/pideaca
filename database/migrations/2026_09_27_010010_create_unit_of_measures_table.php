<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 20);
            $table->decimal('base_conversion_factor', 10, 4)->default(1);
            $table->boolean('is_integer_only')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->unique('symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};

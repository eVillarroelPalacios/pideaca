<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('product_option_groups')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('extra_price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index('option_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('image_type', 30)->default('banner');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('provider_id');
            $table->index(['provider_id', 'image_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_images');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('business_name');
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->string('promo')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('zone')->nullable();
            $table->json('hours')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('category_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};

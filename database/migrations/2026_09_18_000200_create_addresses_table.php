<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained();
            $table->foreignId('province_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('postal_code', 20)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 30)->nullable();
            $table->string('floor_apartment', 40)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('province_id');
            $table->index('department_id');
            $table->index(['country_id', 'province_id']);
            $table->index(['country_id', 'department_id']);
            $table->index(['user_id', 'is_primary', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
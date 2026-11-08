<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8);
            $table->string('symbol_position', 8)->default('before'); // before | after
            $table->boolean('symbol_space')->default(true);
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->char('decimal_separator', 1)->default(',');
            $table->char('thousand_separator', 1)->default('.');
            $table->string('locale', 12)->default('es-AR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

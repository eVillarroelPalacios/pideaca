<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('regions')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->string('type', 30); // region | province | department | municipality | locality
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['country_id', 'parent_id']);
            $table->index(['country_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
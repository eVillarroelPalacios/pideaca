<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('active'); // active, inactive
            $table->timestamps();

            $table->unique(['provider_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_drivers');
    }
};

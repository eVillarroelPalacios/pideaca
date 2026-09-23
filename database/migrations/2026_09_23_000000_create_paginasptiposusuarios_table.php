<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginasptiposusuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_user_id')->constrained('type_users')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['type_user_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paginasptiposusuarios');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_users', function (Blueprint $table) {
            $table->id();
            $table->string('description')->unique();
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('description')->unique();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('url')->nullable();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('page_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'page_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('type_user_id')->nullable()->constrained('type_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['type_user_id']);
            $table->dropColumn('type_user_id');
        });
        Schema::dropIfExists('page_user');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('type_users');
    }
};
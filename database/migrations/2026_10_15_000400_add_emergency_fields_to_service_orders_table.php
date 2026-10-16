<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->text('description')->nullable()->after('scheduled_at');
            $table->string('photo_path')->nullable()->after('description');
            $table->string('audio_path')->nullable()->after('photo_path');
            $table->text('estimate_details')->nullable()->after('additional_cost');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['description', 'photo_path', 'audio_path', 'estimate_details']);
        });
    }
};

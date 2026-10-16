<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('completion_photo_path')->nullable()->after('audio_path');
            $table->timestamp('completed_at')->nullable()->after('escrow_status');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('deposit_amount');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->decimal('balance', 12, 2)->default(0)->after('rating');
        });

        Schema::create('service_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['service_order_id', 'reviewer_id']);
            $table->index('reviewer_id');
            $table->index('ratee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ratings');

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('balance');
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['completion_photo_path', 'completed_at', 'commission_amount']);
        });
    }
};

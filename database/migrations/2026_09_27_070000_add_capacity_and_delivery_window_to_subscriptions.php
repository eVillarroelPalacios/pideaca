<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Cupos que el comercio puede abastecer. NULL = sin limite.
            $table->unsignedInteger('capacity')->nullable()->after('discount_percentage');
        });

        Schema::table('customer_subscriptions', function (Blueprint $table) {
            // 1 = lunes ... 7 = domingo (ISO-8601).
            $table->unsignedTinyInteger('preferred_delivery_day')->nullable()->after('next_delivery_date');
            $table->time('preferred_delivery_time')->nullable()->after('preferred_delivery_day');
        });

        DB::statement('ALTER TABLE customer_subscriptions ADD CONSTRAINT customer_subscriptions_preferred_delivery_day_check CHECK (preferred_delivery_day IS NULL OR preferred_delivery_day BETWEEN 1 AND 7)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customer_subscriptions DROP CONSTRAINT IF EXISTS customer_subscriptions_preferred_delivery_day_check');

        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['preferred_delivery_day', 'preferred_delivery_time']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }
};

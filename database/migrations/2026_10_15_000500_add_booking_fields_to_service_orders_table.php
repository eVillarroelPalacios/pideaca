<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('fulfillment', 30)->nullable()->after('scheduled_at');
            $table->string('payment_method', 30)->nullable()->after('fulfillment');
            $table->string('payment_mode', 20)->nullable()->after('payment_method');
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['fulfillment', 'payment_method', 'payment_mode', 'deposit_amount']);
        });
    }
};

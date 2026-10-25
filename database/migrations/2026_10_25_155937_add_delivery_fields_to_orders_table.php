<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_type')) {
                $table->string('delivery_type', 30)->default('delivery'); // pickup, delivery
            }
            if (!Schema::hasColumn('orders', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable();
                $table->index('driver_id');
            }
            if (!Schema::hasColumn('orders', 'delivery_status')) {
                $table->string('delivery_status', 30)->default('pending'); // pending, assigned, picked_up, delivered
                $table->index('delivery_status');
            }
            if (!Schema::hasColumn('orders', 'delivery_pin')) {
                $table->string('delivery_pin', 10)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'driver_id',
                'delivery_status',
                'delivery_pin',
            ]);
        });
    }
};

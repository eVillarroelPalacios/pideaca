<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_of_measure_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->boolean('track_stock')->default(false);
            $table->decimal('min_stock_alert', 10, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_of_measure_id']);
            $table->dropColumn(['unit_of_measure_id', 'track_stock', 'min_stock_alert']);
        });
    }
};

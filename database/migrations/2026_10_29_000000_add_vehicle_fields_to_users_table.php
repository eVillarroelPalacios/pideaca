<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tipo de vehiculo declarado por el repartidor. Es un string y no
            // un enum nativo para poder sumar opciones sin migraciones
            // destructivas en PostgreSQL; los valores validos viven en
            // App\Models\User::VEHICLE_TYPES.
            $table->string('vehicle_type', 30)->nullable()->after('phone');
            $table->string('vehicle_plate', 20)->nullable()->after('vehicle_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type', 'vehicle_plate']);
        });
    }
};

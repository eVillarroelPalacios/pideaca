<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas de Mercado Pago conectadas por OAuth (Marketplace).
     * Una por usuario: con su token se cobra directo al vendedor y la
     * comisión (application_fee) va a PideAca. Tokens siempre cifrados.
     */
    public function up(): void
    {
        Schema::create('mp_seller_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mp_user_id', 40);
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('public_key', 120)->nullable();
            $table->boolean('live_mode')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_seller_accounts');
    }
};

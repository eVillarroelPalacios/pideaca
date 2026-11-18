<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cuentas de cobro de los usuarios (múltiples por usuario/moneda, una
// predeterminada). Sin estas + activa no se opera (gate bloqueante).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('ARS');
            $table->string('label', 100)->nullable(); // alias interno ("Banco X")
            $table->string('holder_name', 255);
            $table->string('cbu', 22)->nullable();
            $table->string('alias_cbu', 100)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('bank', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_accounts');
    }
};

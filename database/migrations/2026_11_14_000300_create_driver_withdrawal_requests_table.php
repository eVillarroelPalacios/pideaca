<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de retiro de saldo del repartidor.
     *
     * El saldo se descuenta de la billetera en el momento de solicitar y el
     * admin aprueba o rechaza desde la pantalla de usuarios. Si se rechaza,
     * el monto se devuelve a la billetera.
     */
    public function up(): void
    {
        Schema::create('driver_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->string('method', 30)->nullable(); // transferencia, efectivo, otro
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_withdrawal_requests');
    }
};

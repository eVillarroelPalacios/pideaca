<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Liquidaciones manuales a comercios/prestadores + libro de movimientos.
// La liquidación guarda foto de montos y cuenta usada (inmutable).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // beneficiario
            $table->string('currency', 3)->default('ARS');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending|paid|cancelled
            $table->string('payout_reference', 255)->nullable(); // comprobante
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->cascadeOnDelete();
            $table->string('reference_type', 50)->nullable(); // order|payment|invoice
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['settlement_id']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = empresa
            $table->string('type', 30); // fee|split|wallet_debit|payout|refund|subscription
            $table->string('currency', 3)->default('ARS');
            $table->decimal('amount', 12, 2)->default(0); // signo según tipo
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'currency']);
            $table->index(['type', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
    }
};

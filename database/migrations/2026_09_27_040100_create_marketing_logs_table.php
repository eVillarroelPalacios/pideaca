<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // El log pertenece a la regla: si el comercio la borra, su historial
            // de envios deja de tener sentido y se va con ella.
            $table->foreignId('campaign_rule_id')->constrained('marketing_campaign_rules')->cascadeOnDelete();

            $table->dateTime('sent_at');
            $table->enum('status', ['SENT', 'FAILED', 'CONVERTED']);

            $table->index('provider_id');
            $table->index('user_id');
            $table->index('campaign_rule_id');
            $table->index('sent_at');
            $table->index(['provider_id', 'sent_at']);
            // Consulta tipica del motor: quien recibio esta regla y como termino.
            $table->index(['campaign_rule_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_logs');
    }
};

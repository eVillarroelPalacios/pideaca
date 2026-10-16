<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            // Cada propuesta pertenece a un prestador concreto: la orden vive
            // en un pool hasta que el cliente acepta una de ellas. El "shell"
            // de solicitud de presupuesto vive sin prestador (null).
            $table->foreignId('provider_id')->nullable()->constrained('providers')->cascadeOnDelete();
            $table->decimal('proposed_price', 10, 2)->default(0);
            $table->text('details')->nullable();
            $table->string('estimated_time')->nullable();
            $table->json('attachments_json')->nullable();
            $table->string('status', 20)->default('PENDING');
            $table->timestamps();

            $table->index('service_order_id');
            $table->index('provider_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_quotes');
    }
};

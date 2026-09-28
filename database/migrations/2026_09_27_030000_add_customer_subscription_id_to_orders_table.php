<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nullable: los pedidos del carrito siguen sin suscripcion.
            // nullOnDelete: el historico del pedido sobrevive a la baja de la suscripcion.
            $table->foreignId('customer_subscription_id')
                ->nullable()
                ->after('user_id')
                ->constrained('customer_subscriptions')
                ->nullOnDelete();

            $table->index('customer_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_subscription_id']);
            $table->dropColumn('customer_subscription_id');
        });
    }
};

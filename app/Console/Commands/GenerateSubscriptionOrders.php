<?php

namespace App\Console\Commands;

use App\Models\CustomerSubscription;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\InventoryService;
use App\Services\SubscriptionBillingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GenerateSubscriptionOrders extends Command
{
    protected $signature = 'subscriptions:generate-orders
                            {--date= : Fecha de corrida (YYYY-MM-DD); por defecto, hoy}';

    protected $description = 'Genera el pedido de cada suscripcion activa con entrega vencida y agenda el siguiente ciclo';

    public function handle(InventoryService $inventory, SubscriptionBillingService $billing): int
    {
        $date = $this->resolveDate();

        if (! $date) {
            return self::FAILURE;
        }

        $subscriptions = CustomerSubscription::query()
            ->active()
            ->due($date)
            ->with(['plan.items.product', 'items.product', 'provider'])
            ->orderBy('id')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No hay suscripciones con entrega vencida al '.$date->toDateString().'.');

            return self::SUCCESS;
        }

        $created = 0;
        $paused = 0;
        $failed = 0;
        $charged = 0;

        foreach ($subscriptions as $subscription) {
            $motivo = $this->motivoDePausa($subscription);

            if ($motivo !== null) {
                $subscription->update(['status' => CustomerSubscription::STATUS_PAUSED]);
                Log::warning('Suscripcion #'.$subscription->id.' pausada automaticamente: '.$motivo);
                $this->warn('Suscripcion #'.$subscription->id.' pausada: '.$motivo);
                $paused++;

                continue;
            }

            try {
                // Orden, cobro, descuento de stock y reprogramacion van en la
                // misma transaccion: o pasa todo, o la suscripcion sigue pendiente.
                [$order, $payment] = DB::transaction(function () use ($subscription, $date, $inventory, $billing) {
                    $periodStart = $subscription->next_delivery_date->copy();

                    $order = $this->createOrder($subscription);

                    $this->applyStock($inventory, $subscription, $order);

                    $payment = $billing->registerCycle(
                        $subscription,
                        $order,
                        $periodStart,
                        $subscription->nextDeliveryDate($date)
                    );

                    $subscription->update([
                        'next_delivery_date' => $payment->period_end,
                    ]);

                    return [$order, $payment];
                });

                if ($billing->charge($payment)->isSettled()) {
                    $charged++;
                }

                $created++;
                $this->info('Suscripcion #'.$subscription->id.': pedido '.$order->order_number.' generado (cobro #'.$payment->id.' '.$payment->status.').');
            } catch (ValidationException $e) {
                // Falta de stock: no se genera el pedido y la suscripcion sigue
                // activa con la fecha vencida, asi reintenta en la proxima corrida.
                $failed++;
                $motivo = collect($e->errors())->flatten()->implode(' ');
                Log::warning('Suscripcion #'.$subscription->id.': no se genero el pedido. '.$motivo);
                $this->warn('Suscripcion #'.$subscription->id.': no se genero el pedido. '.$motivo);
            }
        }

        $this->info('Resumen: '.$created.' generada(s), '.$charged.' cobrada(s), '.$paused.' pausada(s), '.$failed.' pendiente(s).');

        return self::SUCCESS;
    }

    /**
     * Motivo por el que la suscripcion no debe seguir generando pedidos, o
     * null si esta en condiciones de facturar.
     */
    private function motivoDePausa(CustomerSubscription $subscription): ?string
    {
        $plan = $subscription->plan;

        if (! $plan) {
            return 'el plan ya no existe.';
        }

        if (! $plan->is_active) {
            return 'el plan esta inactivo.';
        }

        if (! $subscription->provider || ! $subscription->provider->is_active) {
            return 'el comercio esta inactivo.';
        }

        if ($subscription->itemsForOrder()->isEmpty()) {
            return 'el plan no tiene productos.';
        }

        return null;
    }

    private function createOrder(CustomerSubscription $subscription): Order
    {
        $plan = $subscription->plan;

        $notas = 'Pedido generado automaticamente por la suscripcion #'.$subscription->id.' ('.$plan->title.').';

        if ($subscription->preferred_delivery_time) {
            // La hora preferida no frena a la cocina: queda a la vista para
            // que la entrega se acomode a esa franja.
            $notas .= ' Entrega preferida: '.$subscription->preferred_delivery_time->format('H:i').' hs.';
        }

        $order = Order::create([
            'provider_id' => $subscription->provider_id,
            'user_id' => $subscription->user_id,
            'address_id' => $subscription->delivery_address_id,
            'customer_subscription_id' => $subscription->id,
            'order_number' => Order::nextNumber(),
            'status' => Order::STATUS_PENDING,
            'payment_method' => $subscription->payment_method,
            'notes' => $notas,
        ]);

        foreach ($subscription->itemsForOrder() as $item) {
            $unitPrice = (float) $item['product']->price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $item['quantity'], 2),
            ]);
        }

        // El precio del plan es lo que se cobra: el descuento del plan se aplica
        // sobre ese precio y los productos describen el contenido del pedido.
        $subtotal = (float) $plan->price;
        $discount = $plan->discountAmount();
        $deliveryFee = (float) config('fastdelivery.delivery_fee');

        $order->update([
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'total' => round($subtotal - $discount + $deliveryFee, 2),
        ]);

        return $order;
    }

    private function applyStock(InventoryService $inventory, CustomerSubscription $subscription, Order $order): void
    {
        $lines = [];

        foreach ($subscription->itemsForOrder() as $item) {
            $lines[] = [
                'product' => $item['product'],
                'quantity' => $item['quantity'],
                'key' => 'items.'.$item['product']->id.'.quantity',
            ];
        }

        $inventory->applySale($subscription->provider, $order, $lines);
    }

    private function resolveDate(): ?Carbon
    {
        $option = $this->option('date');

        if (! is_string($option) || $option === '') {
            return today();
        }

        try {
            return Carbon::parse($option)->startOfDay();
        } catch (\Throwable) {
            $this->error('La fecha indicada no es valida: '.$option);

            return null;
        }
    }
}

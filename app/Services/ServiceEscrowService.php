<?php

namespace App\Services;

use App\Models\ServiceOrder;

/**
 * Retencion de fondos en garantia (escrow) de las ordenes de servicio.
 *
 * La garantia vive en service_orders: HOLDING mientras los fondos retenidos
 * esperan la conformidad del cliente, RELEASED cuando se libera al prestador
 * y REFUNDED cuando se devuelve al cliente. El enganche con la pasarela de
 * pago se apoya sobre estos estados.
 */
class ServiceEscrowService
{
    /**
     * Retiene la tarifa base de visita al abrir la orden.
     */
    public function holdVisitFee(ServiceOrder $order): ServiceOrder
    {
        $order->escrow_status = ServiceOrder::ESCROW_HOLDING;
        $order->total_amount = (float) $order->visit_fee;
        $order->save();

        return $order;
    }

    /**
     * Suma al importe retenido el costo adicional aprobado por el cliente.
     */
    public function applyApprovedEstimate(ServiceOrder $order): ServiceOrder
    {
        $order->escrow_status = ServiceOrder::ESCROW_HOLDING;
        $order->total_amount = (float) $order->visit_fee + (float) $order->additional_cost;
        $order->save();

        return $order;
    }

    /**
     * Libera los fondos retenidos al prestador (conformidad del cliente).
     */
    public function release(ServiceOrder $order): ServiceOrder
    {
        if ($order->escrow_status !== ServiceOrder::ESCROW_HOLDING) {
            return $order;
        }

        $order->escrow_status = ServiceOrder::ESCROW_RELEASED;
        $order->save();

        return $order;
    }

    /**
     * Devuelve los fondos retenidos al cliente (cancelacion o reclamo).
     */
    public function refund(ServiceOrder $order): ServiceOrder
    {
        if ($order->escrow_status !== ServiceOrder::ESCROW_HOLDING) {
            return $order;
        }

        $order->escrow_status = ServiceOrder::ESCROW_REFUNDED;
        $order->save();

        return $order;
    }
}

<?php

namespace App\Support;

use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Validation\ValidationException;

/**
 * Puente mínimo entre el checkout y el motor de dinero (Fase 1).
 *
 * Se engancha al guardado del pedido (web y app a la vez) y registra el
 * cobro solo para efectivo/transferencia. Cualquier fallo se reporta sin
 * interrumpir el pedido: el cobro puede cargarse a mano desde Dinero.
 */
class CheckoutCharge
{
    public const METHOD_CASH = 'efectivo';
    public const METHOD_TRANSFER = 'transferencia';

    public static function methodFor(?string $paymentMethod): ?string
    {
        $m = mb_strtolower(trim((string) $paymentMethod));

        if (in_array($m, ['efectivo', 'cash'], true)) {
            return self::METHOD_CASH;
        }

        if (in_array($m, ['transfer', 'transferencia', 'transferencia_bancaria', 'bank_transfer'], true)) {
            return self::METHOD_TRANSFER;
        }

        return null;
    }

    public static function tryRegisterForOrder(Order $order): void
    {
        try {
            $method = self::methodFor($order->payment_method ?? null);

            if ($method === null) {
                return;
            }

            $total = (float) ($order->total ?? 0);

            if ($total <= 0) {
                return;
            }

            app(BillingService::class)->registerManualCharge(
                'mercado',
                'order',
                (int) $order->getKey(),
                $total,
                $method,
                null,
                'ARS'
            );
        } catch (ValidationException $e) {
            // Duplicado u orden inválida: esperado en guardados repetidos.
            if ($e->validator->errors()->has('order')) {
                return;
            }

            report($e);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

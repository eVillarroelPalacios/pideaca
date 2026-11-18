<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\User;
use App\Support\FeeCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Núcleo de dinero manual (Fase 1, sin pasarela).
 *
 * El cobro se REGISTRA a mano desde un pedido existente (uno solo por
 * pedido): se calcula la comisión vigente, se guarda el desglose histórico
 * en el charge y se asienta en el libro. Nada de esto toca el flujo de
 * pedidos: si el pedido no existe o ya tiene cobro, se rechaza.
 */
class BillingService
{
    public static function modules(): array
    {
        return ['mercado' => 'Mercado (Módulo A)', 'hogar' => 'Hogar (Módulo B)'];
    }

    /**
     * Registra un cobro manual para un pedido. Devuelve el charge creado.
     */
    public function registerManualCharge(
        string $module,
        string $orderType,
        int $orderId,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $currency = 'ARS',
        ?User $createdBy = null
    ): Charge {
        if (! isset(self::modules()[$module])) {
            throw ValidationException::withMessages(['module' => 'Módulo inválido.']);
        }

        if (! in_array($method, [Charge::METHOD_TRANSFER, Charge::METHOD_CASH], true)) {
            throw ValidationException::withMessages(['method' => 'Medio inválido.']);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'El monto debe ser mayor a cero.']);
        }

        if (! $this->orderExists($orderType, $orderId)) {
            throw ValidationException::withMessages([
                'order' => 'Ese pedido no existe.',
            ]);
        }

        return DB::transaction(function () use ($module, $orderType, $orderId, $amount, $method, $reference, $currency, $createdBy) {
            if (Charge::where('order_type', $orderType)->where('order_id', $orderId)->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Ese pedido ya tiene un cobro registrado.',
                ]);
            }

            $quote = FeeCalculator::quote($module, $amount, 'AR', $currency);

            $charge = Charge::create([
                'module' => $module,
                'order_type' => $orderType,
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'method' => $method,
                'status' => $method === Charge::METHOD_CASH ? Charge::STATUS_VERIFIED : Charge::STATUS_PENDING,
                'transaction_reference' => $reference,
                'fee_rule_id' => $quote['rule_id'],
                'fee_percent' => $quote['percent'],
                'fee_fixed' => $quote['fixed_fee'],
                'fee_min' => $quote['min_fee'],
                'fee' => $quote['fee'],
                'net' => $quote['net'],
                'created_by' => $createdBy?->id,
            ]);

            if ($method === Charge::METHOD_CASH) {
                // Efectivo: débito inmediato a la billetera de comisiones.
                $ownerId = $this->resolveOwnerId($orderType, $orderId);

                if ($ownerId) {
                    LedgerEntry::create([
                        'user_id' => $ownerId,
                        'type' => LedgerEntry::TYPE_WALLET_DEBIT,
                        'currency' => $currency,
                        'amount' => -$quote['fee'],
                        'reference_type' => 'charge',
                        'reference_id' => $charge->id,
                        'description' => 'Comisión efectivo pedido #' . $orderId,
                    ]);
                }
            }

            LedgerEntry::create([
                'user_id' => null,
                'type' => LedgerEntry::TYPE_FEE,
                'currency' => $currency,
                'amount' => $quote['fee'],
                'reference_type' => 'charge',
                'reference_id' => $charge->id,
                'description' => 'Comisión ' . $module . ' pedido #' . $orderId,
            ]);

            return $charge->fresh();
        });
    }

    /**
     * Confirma un cobro por transferencia (conciliación contra extracto).
     */
    public function verifyTransferCharge(Charge $charge, ?User $actor = null): Charge
    {
        return DB::transaction(function () use ($charge) {
            $locked = Charge::whereKey($charge->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === Charge::STATUS_VERIFIED) {
                return $locked;
            }

            if ($locked->status !== Charge::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'charge' => 'Ese cobro no está pendiente de verificación.',
                ]);
            }

            $locked->update(['status' => Charge::STATUS_VERIFIED]);

            return $locked->fresh();
        });
    }

    /**
     * Crea una liquidación con cobros verificados aún no liquidados.
     */
    public function createSettlement(User $beneficiary, string $currency, array $chargeIds, ?User $actor = null, ?string $notes = null): Settlement
    {
        return DB::transaction(function () use ($beneficiary, $currency, $chargeIds, $actor, $notes) {
            $charges = Charge::whereIn('id', $chargeIds)
                ->where('status', Charge::STATUS_VERIFIED)
                ->lockForUpdate()
                ->get();

            if ($charges->isEmpty()) {
                throw ValidationException::withMessages([
                    'charges' => 'No hay cobros verificados para liquidar.',
                ]);
            }

            $already = SettlementItem::where('reference_type', 'charge')
                ->whereIn('reference_id', $charges->pluck('id')->all())
                ->exists();

            if ($already) {
                throw ValidationException::withMessages([
                    'charges' => 'Alguno de esos cobros ya está en otra liquidación.',
                ]);
            }

            $gross = (float) $charges->sum('amount');
            $fee = (float) $charges->sum('fee');

            $settlement = Settlement::create([
                'user_id' => $beneficiary->id,
                'currency' => $currency,
                'gross' => $gross,
                'fee' => $fee,
                'net' => round($gross - $fee, 2),
                'status' => Settlement::STATUS_PENDING,
                'created_by' => $actor?->id,
                'notes' => $notes,
            ]);

            foreach ($charges as $c) {
                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'reference_type' => 'charge',
                    'reference_id' => $c->id,
                    'amount' => (float) $c->amount,
                ]);
            }

            return $settlement->fresh(['items']);
        });
    }

    /**
     * Paga una liquidación (se sube el comprobante de la transferencia).
     */
    public function paySettlement(Settlement $settlement, string $reference, ?User $actor = null): Settlement
    {
        return DB::transaction(function () use ($settlement, $reference) {
            $locked = Settlement::whereKey($settlement->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === Settlement::STATUS_PAID) {
                return $locked;
            }

            if ($locked->status !== Settlement::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'settlement' => 'Esa liquidación no está pendiente.',
                ]);
            }

            $locked->update([
                'status' => Settlement::STATUS_PAID,
                'payout_reference' => $reference,
                'paid_at' => now(),
            ]);

            LedgerEntry::create([
                'user_id' => null,
                'type' => LedgerEntry::TYPE_PAYOUT,
                'currency' => $locked->currency,
                'amount' => -$locked->net,
                'reference_type' => 'settlement',
                'reference_id' => $locked->id,
                'description' => 'Liquidación #' . $locked->id . ' usuario #' . $locked->user_id,
            ]);

            return $locked->fresh(['items']);
        });
    }

    public function cancelSettlement(Settlement $settlement): Settlement
    {
        $settlement->update(['status' => Settlement::STATUS_CANCELLED]);

        return $settlement->fresh(['items']);
    }

    /**
     * Deuda de comisiones en efectivo (negativo = debe).
     */
    public static function cashDebt(User $user, string $currency = 'ARS'): float
    {
        $in = (float) LedgerEntry::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', LedgerEntry::TYPE_WALLET_DEBIT)
            ->sum('amount');

        $paid = (float) LedgerEntry::where('user_id', $user->id)
            ->where('currency', $currency)
            ->where('type', LedgerEntry::TYPE_COLLECTION)
            ->sum('amount');

        return round($in + $paid, 2);
    }

    /**
     * Registra un cobro de deuda de efectivo (transferencia conciliada).
     */
    public function collectCashDebt(User $user, float $amount, string $currency = 'ARS', ?string $reference = null): LedgerEntry
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'El monto debe ser mayor a cero.']);
        }

        return LedgerEntry::create([
            'user_id' => $user->id,
            'type' => LedgerEntry::TYPE_COLLECTION,
            'currency' => $currency,
            'amount' => $amount,
            'reference_type' => null,
            'reference_id' => null,
            'description' => 'Cobro deuda efectivo' . ($reference ? ' (' . $reference . ')' : ''),
        ]);
    }

    private function orderExists(string $orderType, int $orderId): bool
    {
        try {
            if ($orderType === 'service_order' && class_exists(\App\Models\ServiceOrder::class)) {
                return \App\Models\ServiceOrder::whereKey($orderId)->exists();
            }

            if (class_exists(\App\Models\Order::class)) {
                return \App\Models\Order::whereKey($orderId)->exists();
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function resolveOwnerId(string $orderType, int $orderId): ?int
    {
        // Mercado: el comercio es el proveedor del pedido; Hogar: el prestador.
        // Se resuelve de forma tolerante: si el pedido no existe, null.
        try {
            if ($orderType === 'service_order' && class_exists(\App\Models\ServiceOrder::class)) {
                $o = \App\Models\ServiceOrder::find($orderId);

                return $o?->provider_id ?? $o?->merchant_id ?? null;
            }

            if (class_exists(\App\Models\Order::class)) {
                $o = \App\Models\Order::find($orderId);

                return $o?->provider_id ?? null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}

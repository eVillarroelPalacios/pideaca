<?php

namespace App\Services;

use App\Models\DriverWallet;
use App\Models\DriverWithdrawalRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operaciones de la billetera del repartidor.
 *
 * El retiro se congela al solicitarlo (se descuenta el saldo en el acto)
 * para que no se pueda retirar dos veces el mismo dinero. El admin lo
 * aprueba o rechaza; un rechazo devuelve el monto a la billetera.
 */
class DriverWalletService
{
    public function requestWithdrawal(
        User $user,
        float $amount,
        ?string $method = null,
        ?string $notes = null
    ): DriverWithdrawalRequest {
        $min = (float) config('fastdelivery.min_withdrawal', 1000);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Ingresá un monto válido para retirar.',
            ]);
        }

        if ($amount < $min) {
            throw ValidationException::withMessages([
                'amount' => 'El monto mínimo de retiro es '.number_format($min, 0, ',', '.').'.',
            ]);
        }

        return DB::transaction(function () use ($user, $amount, $method, $notes) {
            $wallet = DriverWallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet || (float) $wallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto supera tu saldo disponible en la billetera.',
                ]);
            }

            $hasPending = DriverWithdrawalRequest::where('user_id', $user->id)
                ->where('status', DriverWithdrawalRequest::STATUS_PENDING)
                ->exists();

            if ($hasPending) {
                throw ValidationException::withMessages([
                    'amount' => 'Ya tenés una solicitud de retiro pendiente de aprobación.',
                ]);
            }

            // El saldo queda congelado desde el momento de la solicitud.
            $wallet->balance = (float) $wallet->balance - $amount;
            $wallet->save();

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'withdrawal',
                'status' => 'pending',
                'description' => 'Solicitud de retiro de saldo',
            ]);

            $request = DriverWithdrawalRequest::create([
                'user_id' => $user->id,
                'wallet_transaction_id' => $transaction->id,
                'amount' => $amount,
                'status' => DriverWithdrawalRequest::STATUS_PENDING,
                'method' => $method,
                'notes' => $notes,
            ]);

            $this->tryAutoApprove($request);

            return $request->fresh(['user:id,name,email']);
        });
    }

    /**
     * Auto-aprobación por tope del comercio: si algún comercio donde el
     * repartidor es de confianza tiene tope configurado y cubre el monto,
     * se aprueba solo al instante (reviewed_by queda en null).
     */
    private function tryAutoApprove(DriverWithdrawalRequest $request): void
    {
        $providerId = \App\Models\ProviderDriver::where('user_id', $request->user_id)
            ->where('status', 'active')
            ->whereHas('provider', function ($q) use ($request) {
                $q->whereNotNull('auto_approve_max')
                    ->where('auto_approve_max', '>=', (float) $request->amount);
            })
            ->orderBy('provider_id')
            ->value('provider_id');

        if (! $providerId) {
            return;
        }

        $locked = DriverWithdrawalRequest::whereKey($request->getKey())->lockForUpdate()->first();

        if (! $locked || $locked->status !== DriverWithdrawalRequest::STATUS_PENDING) {
            return;
        }

        $notes = trim((string) $locked->notes);
        $notes = trim($notes . ' | Aprobación automática (tope del comercio)');

        $locked->update([
            'status' => DriverWithdrawalRequest::STATUS_APPROVED,
            'reviewed_by' => null,
            'reviewed_at' => now(),
            'notes' => $notes !== '' ? $notes : null,
        ]);

        if ($locked->wallet_transaction_id) {
            WalletTransaction::whereKey($locked->wallet_transaction_id)
                ->update(['status' => 'completed']);
        }
    }

    public function approve(DriverWithdrawalRequest $request, User $admin): DriverWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $admin) {
            $locked = DriverWithdrawalRequest::whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertPending($locked);

            $locked->update([
                'status' => DriverWithdrawalRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($locked->wallet_transaction_id) {
                WalletTransaction::whereKey($locked->wallet_transaction_id)
                    ->update(['status' => 'completed']);
            }

            return $locked->fresh(['user:id,name,email']);
        });
    }

    public function reject(DriverWithdrawalRequest $request, User $admin, ?string $reason = null): DriverWithdrawalRequest
    {
        return DB::transaction(function () use ($request, $admin, $reason) {
            $locked = DriverWithdrawalRequest::whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertPending($locked);

            // Devolver el monto congelado a la billetera.
            $wallet = DriverWallet::where('user_id', $locked->user_id)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                $wallet->balance = (float) $wallet->balance + (float) $locked->amount;
                $wallet->save();
            }

            if ($locked->wallet_transaction_id) {
                WalletTransaction::whereKey($locked->wallet_transaction_id)
                    ->update(['status' => 'cancelled']);
            }

            WalletTransaction::create([
                'user_id' => $locked->user_id,
                'amount' => (float) $locked->amount,
                'type' => 'credit',
                'status' => 'completed',
                'description' => 'Devolución de retiro rechazado #'.$locked->id,
            ]);

            $notes = trim((string) $locked->notes);
            if ($reason !== null && trim($reason) !== '') {
                $notes = trim($notes.' | Motivo: '.$reason);
            }

            $locked->update([
                'status' => DriverWithdrawalRequest::STATUS_REJECTED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'notes' => $notes !== '' ? $notes : null,
            ]);

            return $locked->fresh(['user:id,name,email']);
        });
    }

    private function assertPending(DriverWithdrawalRequest $request): void
    {
        if ($request->status !== DriverWithdrawalRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Esta solicitud de retiro ya fue procesada.',
            ]);
        }
    }
}

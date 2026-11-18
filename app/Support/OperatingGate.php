<?php

namespace App\Support;

use App\Models\FeeRule;
use App\Services\BillingService;
use App\Services\MembershipService;
use App\Services\PayoutAccountService;

/**
 * Estado operativo del usuario para el panel y la app.
 *
 * - can_operate: tiene al menos una billetera activa.
 * - cash_debt: deuda de comisiones en efectivo (por moneda).
 * - membership: estado de la suscripción Hogar (si es prestador).
 */
class OperatingGate
{
    public static function status($user, string $currency = 'ARS'): array
    {
        $membership = null;

        if ($user->membershipSubscription) {
            $sub = $user->membershipSubscription;
            $open = \App\Models\MembershipInvoice::where('user_id', $user->id)
                ->whereIn('status', [
                    \App\Models\MembershipInvoice::STATUS_PENDING,
                    \App\Models\MembershipInvoice::STATUS_OVERDUE,
                ])
                ->orderBy('due_date')
                ->first();

            $membership = [
                'subscribed' => $sub->status === \App\Models\MembershipSubscription::STATUS_ACTIVE,
                'plan_id' => $sub->membership_plan_id,
                'suspended' => MembershipService::isSuspended($user),
                'open_invoice' => $open ? [
                    'id' => $open->id,
                    'amount' => (float) $open->amount,
                    'currency' => $open->currency,
                    'due_date' => $open->due_date?->format('Y-m-d'),
                    'status' => $open->status,
                    'reminders_sent' => (int) $open->reminders_sent,
                ] : null,
            ];
        }

        return [
            'can_operate' => PayoutAccountService::canOperate($user),
            'cash_debt' => BillingService::cashDebt($user, $currency),
            'currency' => $currency,
            'membership' => $membership,
            'fee_rules' => FeeRule::where('active', true)->orderBy('module')->orderBy('id')->get([
                'id', 'module', 'country', 'currency', 'percent', 'fixed_fee', 'min_fee', 'valid_from', 'valid_to',
            ])->all(),
        ];
    }
}

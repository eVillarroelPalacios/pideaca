<?php

namespace App\Services;

use App\Mail\MembershipReminderMail;
use App\Models\MembershipInvoice;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Membresías Hogar: planes configurables, facturación, mora y
 * reactivación. La deuda se congela al suspender (no es acumulativa).
 */
class MembershipService
{
    public const REMINDER_DAYS = [0, 5, 10, 13];

    public function createPlan(array $data): MembershipPlan
    {
        return MembershipPlan::create([
            'name' => trim((string) ($data['name'] ?? '')),
            'module' => 'hogar',
            'currency' => strtoupper(trim((string) ($data['currency'] ?? 'ARS'))),
            'price' => (float) ($data['price'] ?? 0),
            'billing_days' => (int) ($data['billing_days'] ?? 30),
            'grace_days' => (int) ($data['grace_days'] ?? 15),
            'active' => (bool) ($data['active'] ?? true),
        ]);
    }

    /**
     * El prestador elige plan: se crea la suscripción + primera factura.
     */
    public function subscribe(User $user, MembershipPlan $plan, ?\DateTimeInterface $now = null): MembershipInvoice
    {
        $now = $now ? \Carbon\Carbon::parse($now) : now();

        if (! $plan->active) {
            throw ValidationException::withMessages(['plan' => 'Ese plan no está disponible.']);
        }

        return DB::transaction(function () use ($user, $plan, $now) {
            MembershipSubscription::updateOrCreate(
                ['user_id' => $user->id],
                ['membership_plan_id' => $plan->id, 'status' => MembershipSubscription::STATUS_ACTIVE, 'started_at' => $now, 'cancelled_at' => null]
            );

            return $this->emitInvoice($user, $plan, $now);
        });
    }

    public function emitInvoice(User $user, MembershipPlan $plan, ?\DateTimeInterface $now = null): MembershipInvoice
    {
        $now = $now ? \Carbon\Carbon::parse($now) : now();
        $start = (clone $now)->startOfDay();

        return MembershipInvoice::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->addDays($plan->billing_days)->toDateString(),
            'due_date' => $start->copy()->addDays($plan->billing_days)->toDateString(),
            'status' => MembershipInvoice::STATUS_PENDING,
        ]);
    }

    /**
     * Paga una factura. Si el usuario estaba suspendido por mora y no le
     * quedan impagas, se reactiva solo.
     */
    public function payInvoice(MembershipInvoice $invoice, ?string $reference = null): MembershipInvoice
    {
        return DB::transaction(function () use ($invoice, $reference) {
            $locked = MembershipInvoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === MembershipInvoice::STATUS_PAID) {
                return $locked;
            }

            $locked->update(['status' => MembershipInvoice::STATUS_PAID, 'paid_at' => now(), 'payment_reference' => $reference]);

            $user = $locked->user;

            $pending = MembershipInvoice::where('user_id', $locked->user_id)
                ->whereIn('status', [MembershipInvoice::STATUS_PENDING, MembershipInvoice::STATUS_OVERDUE])
                ->exists();

            if (! $pending && $this->isSuspended($user)) {
                $user->user_status_id = UserStatus::firstOrCreate(['status' => 'Activo'])->id;
                $user->save();
            }

            return $locked->fresh();
        });
    }

    public static function isSuspended(User $user): bool
    {
        return mb_strtolower(trim((string) ($user->status?->status ?? ''))) === 'suspendido';
    }

    /**
     * Proceso diario: emite, recuerda y suspende. Devuelve resumen.
     */
    public function runDaily(?\DateTimeInterface $now = null): array
    {
        $now = $now ? \Carbon\Carbon::parse($now) : now();
        $out = ['emitted' => 0, 'reminded' => 0, 'suspended' => 0];

        // 1) Facturar: suscripción activa sin factura vigente ni pendiente.
        foreach (MembershipSubscription::with('plan')->where('status', MembershipSubscription::STATUS_ACTIVE)->cursor() as $sub) {
            if (! $sub->plan || ! $sub->plan->active) {
                continue;
            }

            $open = MembershipInvoice::where('user_id', $sub->user_id)
                ->whereIn('status', [MembershipInvoice::STATUS_PENDING, MembershipInvoice::STATUS_OVERDUE])
                ->exists();

            if ($open) {
                continue;
            }

            $last = MembershipInvoice::where('user_id', $sub->user_id)
                ->where('status', MembershipInvoice::STATUS_PAID)
                ->orderByDesc('period_end')
                ->first();

            $start = $last ? \Carbon\Carbon::parse($last->period_end) : $now->copy()->startOfDay();

            if ($start->greaterThan($now)) {
                continue;
            }

            $this->emitInvoice($sub->user, $sub->plan, $start);
            $out['emitted']++;
        }

        // 2) Recordatorios + 3) suspensión.
        $suspendedStatusId = UserStatus::firstOrCreate(['status' => 'Suspendido'])->id;

        foreach (MembershipInvoice::with('user')
            ->whereIn('status', [MembershipInvoice::STATUS_PENDING, MembershipInvoice::STATUS_OVERDUE])
            ->cursor() as $inv
        ) {
            $due = \Carbon\Carbon::parse($inv->due_date)->startOfDay();
            // Días de mora con aritmética propia (entero con signo: positivo = vencida).
            $overdueDays = (int) floor(
                ($now->copy()->startOfDay()->getTimestamp() - $due->copy()->startOfDay()->getTimestamp()) / 86400
            );

            if ($overdueDays < 0) {
                continue;
            }

            $plan = $inv->plan;
            $grace = $plan ? (int) $plan->grace_days : 15;

            if ($overdueDays >= $grace) {
                $inv->update(['status' => MembershipInvoice::STATUS_OVERDUE]);

                if ($inv->user && ! self::isSuspended($inv->user)) {
                    $inv->user->user_status_id = $suspendedStatusId;
                    $inv->user->save();
                    $out['suspended']++;
                }

                continue;
            }

            if (in_array($overdueDays, self::REMINDER_DAYS, true)
                && (int) $inv->reminders_sent < 4
            ) {
                try {
                    Mail::to($inv->user->email)->send(new MembershipReminderMail($inv->user->name, $inv, $overdueDays, $grace));
                } catch (\Throwable $e) {
                    report($e);
                    continue;
                }

                $inv->update(['reminders_sent' => $inv->reminders_sent + 1, 'last_reminder_at' => $now]);
                $out['reminded']++;
            }
        }

        return $out;
    }
}

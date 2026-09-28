<?php

namespace App\Services;

use App\Models\MarketingCampaignRule;
use App\Models\MarketingLog;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor de retencion automatica.
 *
 * Recorre las reglas INACTIVE_CUSTOMER habilitadas, calcula cuantos dias lleva
 * sin comprar cada cliente del comercio (segun su ultimo pedido) y les envia el
 * mensaje de recuperacion. Cada intento queda registrado en marketing_logs,
 * que es lo que impide repetir un envio el mismo dia.
 */
class RetentionCampaignService
{
    public function __construct(private readonly MarketingMessageSender $sender) {}

    /**
     * @return array{
     *     rules: int, sent: int, failed: int, skipped_duplicate: int,
     *     skipped_no_phone: int, skipped_overdue: int, details: array<int, string>
     * }
     */
    public function run(?Carbon $reference = null): array
    {
        $reference = ($reference ?? today())->copy()->startOfDay();

        $rules = MarketingCampaignRule::query()
            ->enabled()
            ->ofType(MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER)
            ->with('provider')
            ->orderBy('id')
            ->get();

        $resumen = [
            'rules' => $rules->count(),
            'sent' => 0,
            'failed' => 0,
            'skipped_duplicate' => 0,
            'skipped_no_phone' => 0,
            'skipped_overdue' => 0,
            'details' => [],
        ];

        if ($rules->isEmpty()) {
            return $resumen;
        }

        $ultimoPedido = $this->ultimaCompraPorCliente($rules->pluck('provider_id')->all());

        foreach ($rules as $rule) {
            $clientes = $ultimoPedido[$rule->provider_id] ?? [];

            if ($clientes === []) {
                continue;
            }

            $enviados = 0;
            $tope = (int) config('marketing.max_sends_per_provider', 500);

            foreach ($clientes as $userId => $ultimaCompra) {
                if ($enviados >= $tope) {
                    Log::warning('Retencion: comercio '.$rule->provider_id.' alcanzo el tope de '.$tope.' envios; el resto se evalua manana.');

                    break;
                }

                $dias = (int) $ultimaCompra->copy()->startOfDay()->diffInDays($reference);

                if (! $this->dentroDeVentana($rule, $dias)) {
                    $this->registrarFueraDeVentana($rule, $userId, $dias);

                    continue;
                }

                $user = User::find($userId);

                if (! $user) {
                    continue;
                }

                if ($this->yaAvisado($rule, $user, $reference)) {
                    $resumen['skipped_duplicate']++;

                    continue;
                }

                if (! is_string($user->phone) || $user->phone === '') {
                    $resumen['skipped_no_phone']++;
                    Log::info('Retencion: usuario '.$user->id.' sin telefono, se omite la regla '.$rule->id.'.');

                    continue;
                }

                $resultado = $this->enviar($rule, $user, $dias);

                if ($resultado) {
                    $enviados++;
                    $resumen['sent']++;
                    $resumen['details'][] = 'usuario '.$user->id.' ('.$dias.' dias)';
                } else {
                    $resumen['failed']++;
                }
            }
        }

        return $resumen;
    }

    /**
     * Envia el mensaje y deja el log. El log se escribe antes del envio para
     * que una caida a mitad de camino no provoque un reenvio al dia siguiente.
     */
    private function enviar(MarketingCampaignRule $rule, User $user, int $dias): bool
    {
        $mensaje = $rule->renderMessage([
            'nombre' => $user->name,
            'dias' => $dias,
            'comercio' => $rule->provider->business_name,
            'cupon' => $rule->discount_code,
        ]);

        return DB::transaction(function () use ($rule, $user, $mensaje) {
            $log = MarketingLog::create([
                'provider_id' => $rule->provider_id,
                'user_id' => $user->id,
                'campaign_rule_id' => $rule->id,
                'sent_at' => now(),
                'status' => MarketingLog::STATUS_SENT,
            ]);

            try {
                $resultado = $this->sender->send($rule, $user, $rule->provider, $mensaje);
            } catch (\Throwable $e) {
                $log->update(['status' => MarketingLog::STATUS_FAILED]);
                Log::error('Retencion: fallo el envio de la regla '.$rule->id.' al usuario '.$user->id.': '.$e->getMessage());

                return false;
            }

            if (! $resultado['sent']) {
                $log->update(['status' => MarketingLog::STATUS_FAILED]);
                Log::warning('Retencion: el gateway no acepto el mensaje de la regla '.$rule->id.' al usuario '.$user->id.': '.($resultado['detail'] ?? 'sin detalle'));

                return false;
            }

            return true;
        });
    }

    /**
     * Ultima compra de cada cliente por comercio, en una sola consulta.
     * Los pedidos cancelados no cuentan como compra.
     *
     * @param  array<int, int>  $providerIds
     * @return array<int, array<int, Carbon>> [provider_id][user_id] => fecha
     */
    private function ultimaCompraPorCliente(array $providerIds): array
    {
        if ($providerIds === []) {
            return [];
        }

        return Order::query()
            ->whereIn('provider_id', $providerIds)
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->selectRaw('provider_id, user_id, MAX(created_at) as last_order_at')
            ->groupBy('provider_id', 'user_id')
            ->get()
            ->groupBy('provider_id')
            ->map(fn ($pedidos) => $pedidos->mapWithKeys(
                fn ($pedido) => [(int) $pedido->user_id => Carbon::parse($pedido->last_order_at)]
            )->all())
            ->all();
    }

    /**
     * El envio ya quedo registrado hoy para este cliente y esta regla.
     */
    private function yaAvisado(MarketingCampaignRule $rule, User $user, Carbon $reference): bool
    {
        return MarketingLog::query()
            ->where('campaign_rule_id', $rule->id)
            ->where('user_id', $user->id)
            ->delivered()
            ->sentOn($reference)
            ->exists();
    }

    /**
     * Ventana de la campana: por defecto el numero exacto de dias configurado
     * (dias_inactive). Si el cron dejo de correr, marketing.inactive_grace_days
     * amplia el rango para que esos clientes no se pierdan.
     */
    private function dentroDeVentana(MarketingCampaignRule $rule, int $dias): bool
    {
        $minimo = (int) $rule->days_inactive;
        $tolerancia = max(0, (int) config('marketing.inactive_grace_days', 0));

        return $dias >= $minimo && $dias <= $minimo + $tolerancia;
    }

    /**
     * Un cliente que ya supero la ventana no va a recibir nada: queda
     * registrado en el log para que el comercio vea que la campana se perdio.
     */
    private function registrarFueraDeVentana(MarketingCampaignRule $rule, int $userId, int $dias): void
    {
        static $avisados = [];

        $clave = $rule->id.':'.$userId;
        $tolerancia = max(0, (int) config('marketing.inactive_grace_days', 0));

        if (isset($avisados[$clave]) || $dias <= (int) $rule->days_inactive + $tolerancia) {
            return;
        }

        $avisados[$clave] = true;

        Log::warning(sprintf(
            'Retencion: el usuario %d paso la ventana de la regla %d (%d dias inactivo, la regla pide %d). No se le envio nada.',
            $userId,
            $rule->id,
            $dias,
            (int) $rule->days_inactive
        ));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Models\MarketingCampaignRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Reglas de retencion del comercio para la app movil.
 *
 * Espeja MarketingController@index/update del panel web: mismo listado de
 * campañas, mismo resumen y mismas validaciones (tokens obligatorios incluidos).
 * La unica diferencia es el guard: Sanctum en vez de la sesion web.
 */
class ProviderMarketingController extends Controller
{
    use ResolvesProvider;

    /**
     * GET /api/mobile/v1/provider/marketing/rules
     *
     * Reglas del comercio con su estado actual, los tipos disponibles y las
     * plantillas por defecto que usa la app cuando la campaña no existe.
     */
    public function index(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $rules = MarketingCampaignRule::query()
            ->where('provider_id', $provider->id)
            ->orderBy('rule_type')
            ->get();

        return response()->json([
            'success' => true,
            'rules' => $rules->map(fn (MarketingCampaignRule $rule) => $this->rulePayload($rule))->all(),
            'summary' => [
                'total' => $rules->count(),
                'enabled' => $rules->where('is_enabled', true)->count(),
            ],
            'types' => MarketingCampaignRule::TYPES,
            'default_templates' => MarketingCampaignRule::DEFAULT_TEMPLATES,
        ]);
    }

    /**
     * PUT /api/mobile/v1/provider/marketing/rules
     *
     * Habilita y personaliza una regla. Si la regla de ese tipo todavia no
     * existe para el comercio, la crea; si existe, la actualiza.
     */
    public function update(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $datos = $request->validate([
            'rule_type' => ['nullable', 'string', Rule::in(MarketingCampaignRule::TYPES)],
            'days_inactive' => [
                'nullable', 'integer', 'min:1', 'max:365',
                Rule::requiredIf(fn () => $request->input('rule_type', MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER) === MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER),
            ],
            'day_of_week' => [
                'nullable', 'integer', 'min:1', 'max:7',
                Rule::requiredIf(fn () => $request->input('rule_type') === MarketingCampaignRule::TYPE_RECURRING_DAY_REMINDER),
            ],
            'message_template' => [
                'required', 'string', 'max:1000',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $tipo = $request->input('rule_type');

                    if (! is_string($tipo) || ! in_array($tipo, MarketingCampaignRule::TYPES, true)) {
                        $tipo = MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER;
                    }

                    $faltantes = [];

                    foreach (MarketingCampaignRule::requiredTokensFor($tipo) as $token) {
                        if (! str_contains($value, $token)) {
                            $faltantes[] = $token;
                        }
                    }

                    if ($faltantes !== []) {
                        $fail('El mensaje no puede perder '.implode(', ', $faltantes).'; esos tokens se completan solos al enviar.');
                    }
                },
            ],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $tipo = $datos['rule_type'] ?? MarketingCampaignRule::TYPE_INACTIVE_CUSTOMER;

        $rule = MarketingCampaignRule::where('provider_id', $provider->id)
            ->ofType($tipo)
            ->first();

        $valores = [
            'rule_type' => $tipo,
            'days_inactive' => $datos['days_inactive'] ?? null,
            'day_of_week' => $datos['day_of_week'] ?? null,
            'message_template' => $datos['message_template'],
            'discount_code' => $datos['discount_code'] ?? null,
            'is_enabled' => $datos['is_enabled'],
        ];

        try {
            if ($rule) {
                $rule->update($valores);
            } else {
                $rule = MarketingCampaignRule::create($valores + ['provider_id' => $provider->id]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar la regla de marketing.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $rule->wasRecentlyCreated
                ? 'Regla de marketing creada.'
                : 'Regla de marketing actualizada.',
            'rule' => $this->rulePayload($rule->fresh()),
        ], $rule->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function rulePayload(MarketingCampaignRule $rule): array
    {
        return [
            'id' => $rule->id,
            'rule_type' => $rule->rule_type,
            'days_inactive' => $rule->days_inactive,
            'day_of_week' => $rule->day_of_week,
            'message_template' => $rule->message_template,
            'discount_code' => $rule->discount_code,
            'is_enabled' => $rule->is_enabled,
            'default_template' => MarketingCampaignRule::DEFAULT_TEMPLATES[$rule->rule_type] ?? null,
            'created_at' => $rule->created_at?->toDateTimeString(),
            'updated_at' => $rule->updated_at?->toDateTimeString(),
        ];
    }
}

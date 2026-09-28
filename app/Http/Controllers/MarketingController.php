<?php

namespace App\Http\Controllers;

use App\Models\MarketingCampaignRule;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;

class MarketingController extends Controller
{
    /**
     * GET /api/v1/provider/marketing/rules
     *
     * Devuelve las reglas del comercio con su estado actual, para que el
     * comercio pueda ver cuales estan habilitadas y cual es la que falta
     * configurar.
     */
    public function index(): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

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
     * PUT /api/v1/provider/marketing/rules
     *
     * Habilita y personaliza una regla. Si la regla de ese tipo todavia no
     * existe para el comercio, la crea; si existe, la actualiza.
     */
    public function update(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

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
            'message_template' => ['required', 'string', 'max:1000'],
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

    /**
     * @return Provider|JsonResponse
     */
    private function currentProvider()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = Auth::user()->provider;

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
            ], 403);
        }

        return $provider;
    }
}

<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceRating;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Guardas de rol y payloads compartidos de las ordenes de servicio del
 * Modulo B (urgencias, citas programadas y presupuestos).
 */
trait InteractsWithServiceOrders
{
    /** Grupo del catalogo que habilita los servicios del Modulo B. */
    private const SERVICE_GROUP_NAME = 'Servicios del Hogar & Bienestar';

    /** Mensajes propios de los flujos de solicitud de servicio. */
    private const SERVICE_ORDER_MESSAGES = [
        'sub_group_id.required_without' => 'Elegi un prestador o el tipo de servicio que necesitas.',
    ];

    /**
     * Ids de los SubGrupos que pertenecen al grupo "Servicios del Hogar &
     * Bienestar": el universo valido de servicios que un cliente puede pedir.
     *
     * @return array<int, int>
     */
    private function serviceSubgroupIds(): array
    {
        return SubGroup::query()
            ->whereHas('group', fn ($q) => $q->where('description', self::SERVICE_GROUP_NAME))
            ->pluck('id')
            ->all();
    }

    /**
     * SubGrupos del grupo hogar que ofrece un prestador.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SubGroup>
     */
    private function providerHogarSubgroups(Provider $provider)
    {
        return $provider->subgroups()
            ->whereHas('group', fn ($q) => $q->where('description', self::SERVICE_GROUP_NAME))
            ->orderBy('description')
            ->get();
    }

    /**
     * Resuelve el prestador elegido por el cliente para el flujo directo
     * (la orden se le asigna a el, no entra al pool). Valida que este
     * activo, que ofrezca el tipo de servicio pedido y que pertenezca al
     * grupo hogar. Devuelve el Provider o un 404/422 como JsonResponse.
     *
     * @return Provider|JsonResponse
     */
    private function resolveDirectProvider(int $providerId, string $serviceTypeCode)
    {
        $provider = Provider::query()
            ->where('is_active', true)
            ->whereHas('serviceTypes', fn ($q) => $q->where('code', $serviceTypeCode))
            ->whereHas('subgroups.group', fn ($q) => $q->where('description', self::SERVICE_GROUP_NAME))
            ->find($providerId);

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'El prestador elegido ya no ofrece este tipo de servicio.',
            ], 404);
        }

        return $provider;
    }

    /**
     * Cuerpo de una solicitud de servicio ya normalizado.
     *
     * El formulario historicamente mando el cuerpo JSON sin la cabecera
     * "Content-Type: application/json": Laravel no lo parseaba, el body
     * llegaba vacio y el prestador elegido se perdia (la validacion pedia el
     * subgrupo porque no encontraba el prestador). Para que el flujo no
     * dependa de esa cabecera se decodifica el cuerpo crudo cuando hace
     * falta y se aceptan los alias de nombre que usan algunos clientes.
     *
     * @return array<string, mixed>
     */
    private function serviceOrderPayload(Request $request): array
    {
        $payload = $request->all();

        if (! $request->isJson()) {
            $raw = trim((string) $request->getContent());

            if ($raw !== '' && ($raw[0] === '{' || $raw[0] === '[')) {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $payload = array_replace($payload, $decoded);
                }
            }
        }

        // Alias admitidos para el prestador y el subgrupo.
        if (blank($payload['provider_id'] ?? null) && filled($payload['prestador_id'] ?? null)) {
            $payload['provider_id'] = $payload['prestador_id'];
        }

        if (blank($payload['sub_group_id'] ?? null)) {
            foreach (['subgrupo_id', 'subgroup_id'] as $alias) {
                if (filled($payload[$alias] ?? null)) {
                    $payload['sub_group_id'] = $payload[$alias];
                    break;
                }
            }
        }

        return $payload;
    }

    /**
     * Datos validados de una solicitud de servicio (urgencia, cita o
     * presupuesto) usando el cuerpo normalizado.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>
     */
    private function serviceOrderData(Request $request, array $rules, array $messages = []): array
    {
        return Validator::make(
            $this->serviceOrderPayload($request),
            $rules,
            array_merge(self::SERVICE_ORDER_MESSAGES, $messages)
        )->validate();
    }

    /**
     * Prestador y subgrupo de la orden: cuando el cliente elige un prestador
     * concreto, el subgrupo se deriva de los subgrupos del hogar que ofrece
     * el prestador (no del que envie el cliente).
     *
     * @param  array<string, mixed>  $data
     * @return array{provider_id: int|null, sub_group_id: int|null}|JsonResponse
     */
    private function resolveServiceOrderTarget(array $data, string $serviceTypeCode)
    {
        if (empty($data['provider_id'])) {
            return [
                'provider_id' => null,
                'sub_group_id' => $data['sub_group_id'] ?? null,
            ];
        }

        $provider = $this->resolveDirectProvider((int) $data['provider_id'], $serviceTypeCode);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $subgroups = $this->providerHogarSubgroups($provider);

        if ($subgroups->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El prestador elegido ya no ofrece este tipo de servicio.',
            ], 404);
        }

        return [
            'provider_id' => $provider->id,
            'sub_group_id' => $subgroups->first()->id,
        ];
    }

    /**
     * Payload de tarjeta de prestador para el browse de Solicitar Servicio
     * (mismo esquema visual que los comercios).
     */
    private function serviceProviderPayload(Provider $provider, Collection $favoriteIds): array
    {
        $provider->loadMissing(['subgroups.group', 'serviceTypes']);

        $availability = $this->providerAvailability($provider);

        return [
            'id' => $provider->id,
            'business_name' => $provider->business_name,
            'description' => $provider->description,
            'zone' => $provider->zone,
            'rating' => $provider->rating,
            'promo' => $provider->promo,
            'phone' => $provider->phone,
            'whatsapp' => $provider->whatsapp,
            'banner_image' => $provider->banner_image,
            'publicidad_image' => $this->publicidadImageUrl($provider),
            // Encabezado del prestador en la ventana de urgencias.
            'is_verified' => (bool) $provider->is_verified,
            'base_visit_fee' => $provider->base_visit_fee !== null ? (float) $provider->base_visit_fee : null,
            'ratings_count' => $this->providerRatingsCount($provider),
            'emergency_count' => $this->providerEmergencyCount($provider),
            'hours' => $provider->hours,
            'is_available_now' => $availability['open'],
            'availability_label' => $availability['label'],
            'eta_minutes' => $availability['eta_minutes'],
            'subgroups' => $provider->subgroups
                ->filter(fn (SubGroup $subgroup) => ($subgroup->group?->description ?? null) === self::SERVICE_GROUP_NAME)
                ->map(fn (SubGroup $subgroup) => [
                    'id' => $subgroup->id,
                    'description' => $subgroup->description,
                ])
                ->values()
                ->all(),
            'service_types' => $provider->serviceTypes
                ->map(fn (ServiceType $type) => [
                    'code' => $type->code,
                    'name' => $type->name,
                ])
                ->values()
                ->all(),
            'is_favorite' => $favoriteIds->contains($provider->id),
        ];
    }

    /**
     * Estado "en vivo" del prestador para el encabezado de la ventana de
     * urgencias: disponible ahora si esta dentro de sus horarios cargados
     * (sin horarios = disponible siempre) y el ETA estimado de llegada.
     *
     * @return array{open: bool, eta_minutes: int, label: string}
     */
    private function providerAvailability(Provider $provider): array
    {
        $open = $this->providerIsOpenNow($provider);
        $eta = $this->providerEtaMinutes($provider);

        return [
            'open' => $open,
            'eta_minutes' => $eta,
            'label' => $open ? 'Disponible Ahora' : 'Llega en ~'.$eta.' min',
        ];
    }

    /**
     * Horarios del prestador (json con claves mon..sun y shifts from/to).
     * Sin horarios cargados se considera disponible las 24 hs.
     */
    private function providerIsOpenNow(Provider $provider): bool
    {
        $hours = $provider->hours;

        if (! is_array($hours) || $hours === []) {
            return true;
        }

        $day = $hours[strtolower(now()->format('D'))] ?? null;

        if (! is_array($day) || empty($day['open'])) {
            return false;
        }

        $now = now()->format('H:i');

        foreach ((array) ($day['shifts'] ?? []) as $shift) {
            $from = $shift['from'] ?? null;
            $to = $shift['to'] ?? null;

            if ($from && $to && $now >= $from && $now <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * ETA del prestador en minutos: promedio entre la creacion de la urgencia
     * y su despacho. Sin historial se estima en 30 minutos (redondeado a 5).
     */
    private function providerEtaMinutes(Provider $provider): int
    {
        $average = ServiceOrder::query()
            ->where('merchant_id', $provider->id)
            ->where('order_type', ServiceOrder::TYPE_EMERGENCY)
            ->whereIn('status', [
                ServiceOrder::STATUS_PROVIDER_DISPATCHED,
                ServiceOrder::STATUS_IN_PROGRESS,
                ServiceOrder::STATUS_COMPLETED,
            ])
            ->get(['created_at', 'updated_at'])
            ->map(fn (ServiceOrder $order) => $order->created_at
                ? (int) round($order->created_at->diffInMinutes($order->updated_at))
                : null)
            ->filter(fn ($minutes) => $minutes !== null && $minutes >= 0)
            ->avg();

        if (! $average) {
            return 30;
        }

        return max(5, min(120, (int) (ceil($average / 5) * 5)));
    }

    /** Urgencias que tomo el prestador (las canceladas no cuentan). */
    private function providerEmergencyCount(Provider $provider): int
    {
        return ServiceOrder::query()
            ->where('merchant_id', $provider->id)
            ->where('order_type', ServiceOrder::TYPE_EMERGENCY)
            ->where('status', '!=', ServiceOrder::STATUS_CANCELLED)
            ->count();
    }

    /** Calificaciones recibidas por el prestador (tabla service_ratings). */
    private function providerRatingsCount(Provider $provider): int
    {
        return ServiceRating::query()
            ->where('ratee_id', $provider->user_id)
            ->count();
    }

    /**
     * Imagen de publicidad del prestador para las tarjetas (mismo criterio
     * que la seccion de comercios): imagenes subidas o provider_<id>.<ext>.
     */
    private function publicidadImageUrl(Provider $provider): ?string
    {
        $provider->loadMissing('images');

        $path = $provider->images->first()?->image_path;

        if (! $path) {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidate = 'provider_'.$provider->id.'.'.$ext;

                if (file_exists(public_path('images/publicidad/'.$candidate))) {
                    $path = $candidate;
                    break;
                }
            }
        }

        return $path ? asset('images/publicidad/'.$path) : null;
    }

    /**
     * @return Provider|JsonResponse
     */
    private function currentProvider()
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
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

    /**
     * Guardia del rol Cliente: cualquier usuario logueado puede solicitar y
     * aprobar servicios; el guard se resuelve contra la tabla users.
     *
     * @return User|JsonResponse
     */
    private function currentCustomer()
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        return Auth::user();
    }

    /**
     * ISO del pais del usuario para localizar nombres (AR/ES). Sin direccion
     * primaria se asume Argentina, igual que las vistas del dashboard.
     */
    private function currentCountry(): string
    {
        $iso = Auth::user()?->primaryAddress?->country?->iso_code;

        return strtoupper((string) ($iso ?: 'AR'));
    }

    private function orderPayload(ServiceOrder $order): array
    {
        $order->loadMissing(['subGroup', 'merchant.user', 'customer', 'quotes.provider', 'ratings']);

        $quote = $order->quotes->first();

        return [
            'id' => $order->id,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'display_status' => $order->displayStatus(),
            'status_label' => $order->displayStatusLabel(),
            'escrow_status' => $order->escrow_status,
            'customer_id' => $order->customer_id,
            'merchant_id' => $order->merchant_id,
            'cancellable' => $order->isCancellable(),
            'customer_rated' => $order->ratings->contains(
                fn ($rating) => (int) $rating->reviewer_id === (int) $order->customer_id
            ),
            'quote' => $quote ? [
                'id' => $quote->id,
                'status' => $quote->status,
                'proposed_price' => $quote->proposed_price,
                'estimated_time' => $quote->estimated_time,
                'details' => $quote->details,
                'provider' => $quote->provider ? [
                    'id' => $quote->provider->id,
                    'business_name' => $quote->provider->business_name,
                ] : null,
                'created_at' => $quote->created_at?->toIso8601String(),
            ] : null,
            // Propuestas pendientes de todos los profesionales del rubro.
            'proposals' => $order->quotes
                ->filter(fn ($propuesta) => $propuesta->status === ServiceQuote::STATUS_PENDING
                    && $propuesta->provider_id
                    && (float) $propuesta->proposed_price > 0)
                ->map(fn ($propuesta) => [
                    'id' => $propuesta->id,
                    'proposed_price' => $propuesta->proposed_price,
                    'estimated_time' => $propuesta->estimated_time,
                    'details' => $propuesta->details,
                    'provider' => $propuesta->provider ? [
                        'id' => $propuesta->provider->id,
                        'business_name' => $propuesta->provider->business_name,
                    ] : null,
                    'created_at' => $propuesta->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'sub_group' => [
                'id' => $order->subGroup->id,
                'description' => $order->subGroup->description,
                'group_id' => $order->subGroup->group_id,
            ],
            'merchant' => $order->merchant ? [
                'id' => $order->merchant->id,
                'business_name' => $order->merchant->business_name,
                'phone' => $order->merchant->phone,
                'whatsapp' => $order->merchant->whatsapp,
                'zone' => $order->merchant->zone,
                // Seguimiento de urgencias: foto, tecnico a cargo y verificacion.
                'photo_url' => $this->publicidadImageUrl($order->merchant),
                'contact_name' => $order->merchant->user?->name,
                'rating' => $order->merchant->rating,
                'is_verified' => (bool) $order->merchant->is_verified,
            ] : null,
            'customer' => [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
            ],
            'latitude' => $order->latitude,
            'longitude' => $order->longitude,
            'scheduled_at' => $order->scheduled_at?->toIso8601String(),
            'fulfillment' => $order->fulfillment,
            'payment_method' => $order->payment_method,
            'payment_mode' => $order->payment_mode,
            'deposit_amount' => $order->deposit_amount,
            'visit_fee' => $order->visit_fee,
            'additional_cost' => $order->additional_cost,
            'total_amount' => $order->total_amount,
            'commission_amount' => $order->commission_amount,
            'description' => $order->description,
            'address' => $order->address,
            'quote_context' => $order->quote_context,
            'address_street' => $order->address_street,
            'address_unit' => $order->address_unit,
            'address_neighborhood' => $order->address_neighborhood,
            'reminder_channel' => $order->reminder_channel,
            'estimate_details' => $order->estimate_details,
            'photo_url' => $order->photo_path ? asset('images/servicios/'.$order->photo_path) : null,
            'audio_url' => $order->audio_path ? asset('images/servicios/'.$order->audio_path) : null,
            'completion_photo_url' => $order->completion_photo_path ? asset('images/servicios/'.$order->completion_photo_path) : null,
            'completed_at' => $order->completed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Guardas de rol y payloads compartidos de las ordenes de servicio del
 * Modulo B (urgencias, citas programadas y presupuestos).
 */
trait InteractsWithServiceOrders
{
    /** Grupo del catalogo que habilita los servicios del Modulo B. */
    private const SERVICE_GROUP_NAME = 'Servicios del Hogar & Bienestar';

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
     * Payload de tarjeta de prestador para el browse de Solicitar Servicio
     * (mismo esquema visual que los comercios).
     */
    private function serviceProviderPayload(Provider $provider, Collection $favoriteIds): array
    {
        $provider->loadMissing(['subgroups.group', 'serviceTypes']);

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
        $order->loadMissing(['subGroup', 'merchant', 'customer', 'quotes.provider', 'ratings']);

        $quote = $order->quotes->first();

        return [
            'id' => $order->id,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
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

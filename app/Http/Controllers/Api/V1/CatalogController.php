<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceRating;
use App\Models\ServiceType;
use App\Models\SubGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Catálogo de prestadores para las apps móviles.
 *
 * Espeja las reglas del browse web (Solicitar Servicio): solo prestadores
 * activos que pertenecen al grupo de servicios del hogar
 * (config catalog.service_group_id) y filtro por tipo de servicio
 * (EMERGENCY / SCHEDULED / QUOTE). La lista es paginada porque en el
 * celular no conviene bajar todo el catálogo de una vez.
 */
class CatalogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $data = $request->validate([
                'search' => ['nullable', 'string', 'max:100'],
                'type' => ['nullable', 'string', Rule::in([
                    ServiceType::CODE_EMERGENCY,
                    ServiceType::CODE_SCHEDULED,
                    ServiceType::CODE_QUOTE,
                ])],
                'favorite' => ['sometimes', 'boolean'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros de búsqueda inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = $request->user();
        $search = trim((string) ($data['search'] ?? ''));
        $favoriteIds = Favorite::where('user_id', $user->id)->pluck('provider_id');

        // Los contadores de las pestañas se calculan con los filtros de
        // búsqueda/tipo, pero antes de aplicar el filtro de favoritos.
        $filtered = $this->baseQuery()
            ->when(! empty($data['type']), fn ($query) => $query
                ->whereHas('serviceTypes', fn ($q) => $q->where('code', $data['type'])))
            ->when($search !== '', function ($query) use ($search) {
                $lower = mb_strtolower($search);
                $query->where(function ($q) use ($lower) {
                    $q->whereRaw('LOWER(business_name) LIKE ?', ['%'.$lower.'%'])
                        ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", ['%'.$lower.'%'])
                        ->orWhereRaw("LOWER(COALESCE(zone, '')) LIKE ?", ['%'.$lower.'%']);
                });
            });

        $totalCount = (clone $filtered)->count();
        $favoritesCount = (clone $filtered)->whereIn('id', $favoriteIds)->count();

        $providers = $filtered
            ->when($request->boolean('favorite'), fn ($query) => $query->whereIn('id', $favoriteIds))
            ->with(['subgroups.group', 'serviceTypes', 'images'])
            ->orderBy('business_name')
            ->paginate($data['per_page'] ?? 15);

        $ratingsCount = $this->ratingsCountByUserId(
            $providers->getCollection()->pluck('user_id')->all()
        );

        return response()->json([
            'success' => true,
            'data' => $providers->getCollection()
                ->map(fn (Provider $provider) => $this->cardPayload($provider, $favoriteIds, $ratingsCount))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'per_page' => $providers->perPage(),
                'total' => $providers->total(),
                'total_count' => $totalCount,
                'favorites_count' => $favoritesCount,
            ],
        ]);
    }

    public function show(Request $request, Provider $provider)
    {
        // El binding encuentra el id, pero igual debe cumplir las reglas del
        // catálogo: activo y del grupo de servicios.
        $visible = $this->baseQuery()->where('providers.id', $provider->id)->exists();

        if (! $visible) {
            return response()->json([
                'success' => false,
                'message' => 'El prestador no está disponible.',
                'code' => 'PROVIDER_NOT_FOUND',
            ], 404);
        }

        $provider->loadMissing(['subgroups.group', 'serviceTypes', 'images', 'services']);

        $user = $request->user();
        $isFavorite = Favorite::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->exists();

        $ratingsCount = $this->ratingsCountByUserId([$provider->user_id]);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $this->cardPayload(
                    $provider,
                    $isFavorite ? collect([$provider->id]) : collect(),
                    $ratingsCount
                ),
                [
                    'hours' => $provider->hours,
                    'eta_minutes' => $this->etaMinutes($provider),
                    'emergency_count' => $this->emergencyCount($provider),
                    'services' => $provider->services
                        ->map(fn ($service) => [
                            'id' => $service->id,
                            'name' => $service->name,
                            'description' => $service->description,
                        ])
                        ->values()
                        ->all(),
                    'images' => $provider->images
                        ->map(fn ($image) => [
                            'id' => $image->id,
                            'image_type' => $image->image_type,
                            'is_primary' => (bool) $image->is_primary,
                            'url' => $this->imageUrl($image->image_path, $image->image_type),
                        ])
                        ->values()
                        ->all(),
                ]
            ),
        ]);
    }

    /**
     * Agrega o quita el prestador de los favoritos del usuario logueado.
     * Devuelve el nuevo estado para que la tarjeta se actualice en el acto.
     */
    public function toggleFavorite(Request $request, Provider $provider)
    {
        $visible = $this->baseQuery()->where('providers.id', $provider->id)->exists();

        if (! $visible) {
            return response()->json([
                'success' => false,
                'message' => 'El prestador no está disponible.',
                'code' => 'PROVIDER_NOT_FOUND',
            ], 404);
        }

        $user = $request->user();

        $existing = Favorite::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'favorites_count' => Favorite::where('user_id', $user->id)->count(),
        ]);
    }

    /**
     * Prestadores visibles en el catálogo: activos y del grupo de servicios.
     * Mismo criterio que el browse web del Módulo B.
     */
    private function baseQuery()
    {
        return Provider::query()
            ->where('is_active', true)
            ->whereHas('subgroups', fn ($q) => $q->where('group_id', $this->serviceGroupId()));
    }

    private function serviceGroupId(): int
    {
        return (int) config('catalog.service_group_id');
    }

    /**
     * Tarjeta de prestador: mismo esquema visual que la web.
     */
    private function cardPayload(Provider $provider, Collection $favoriteIds, array $ratingsCount): array
    {
        $open = $this->isOpenNow($provider);

        return [
            'id' => $provider->id,
            'business_name' => $provider->business_name,
            'description' => $provider->description,
            'zone' => $provider->zone,
            'rating' => $provider->rating !== null ? (float) $provider->rating : null,
            'ratings_count' => $ratingsCount[$provider->user_id] ?? 0,
            'promo' => $provider->promo,
            'phone' => $provider->phone,
            'whatsapp' => $provider->whatsapp,
            'banner_image' => $provider->banner_image,
            'publicidad_image' => $this->publicidadImageUrl($provider),
            'is_verified' => (bool) $provider->is_verified,
            'base_visit_fee' => $provider->base_visit_fee !== null ? (float) $provider->base_visit_fee : null,
            'is_available_now' => $open,
            'availability_label' => $open ? 'Disponible ahora' : 'Cerrado por ahora',
            'subgroups' => $provider->subgroups
                ->filter(fn (SubGroup $subgroup) => (int) $subgroup->group_id === $this->serviceGroupId())
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
     * Calificaciones recibidas por cada usuario-comercio, en una sola query
     * para no hacer una consulta por tarjeta.
     *
     * @param  array<int>  $userIds
     * @return array<int, int>
     */
    private function ratingsCountByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return ServiceRating::query()
            ->selectRaw('ratee_id, COUNT(*) as aggregate')
            ->whereIn('ratee_id', $userIds)
            ->groupBy('ratee_id')
            ->pluck('aggregate', 'ratee_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Disponible ahora si está dentro de sus horarios cargados.
     * Sin horarios = disponible las 24 hs (igual que la web).
     */
    private function isOpenNow(Provider $provider): bool
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
     * ETA en minutos: promedio entre la creación de la urgencia y su
     * despacho. Sin historial se estima en 30 (igual que la web).
     */
    private function etaMinutes(Provider $provider): int
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

    /** Urgencias que tomó el prestador (las canceladas no cuentan). */
    private function emergencyCount(Provider $provider): int
    {
        return ServiceOrder::query()
            ->where('merchant_id', $provider->id)
            ->where('order_type', ServiceOrder::TYPE_EMERGENCY)
            ->where('status', '!=', ServiceOrder::STATUS_CANCELLED)
            ->count();
    }

    /**
     * Imagen de publicidad del prestador para las tarjetas (mismo criterio
     * que la sección de comercios): imágenes subidas o provider_<id>.<ext>.
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

    private function imageUrl(?string $path, ?string $type): ?string
    {
        if (! $path) {
            return null;
        }

        // Los banners y la publicidad viven en images/publicidad, el resto
        // cuelga directo de images/ (igual que en las vistas web).
        if (in_array($type, ['banner', 'publicidad'], true)) {
            return asset('images/publicidad/'.$path);
        }

        return asset('images/'.$path);
    }
}

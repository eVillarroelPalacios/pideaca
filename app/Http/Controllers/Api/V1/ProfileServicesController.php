<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesAccount;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Provider;
use App\Models\ProviderService;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Servicios, categorias (subgrupos) y tipos de servicio del prestador.
 *
 * Replica ProfileController@getProviderSubgroups/toggleProviderSubgroup,
 * getServiceTypes/toggleServiceType y ProviderServiceController sin tocar la
 * web. El secundario opera sobre el negocio del principal.
 */
class ProfileServicesController extends Controller
{
    use ResolvesAccount;

    public function index(Request $request): JsonResponse
    {
        $provider = $this->accountProvider($request->user());

        if (! $provider) {
            return response()->json([
                'success' => true,
                'services' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'services' => $provider->services()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (ProviderService $service) => $this->servicePayload($service))
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $provider = $this->accountProvider($request->user());

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debés configurar tu perfil de proveedor.',
            ], 400);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Ingresá el nombre del servicio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
        ]);

        $maxOrder = ProviderService::where('provider_id', $provider->id)->max('sort_order');

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => ($maxOrder ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio agregado correctamente.',
            'service' => $this->servicePayload($service),
        ], 201);
    }

    public function update(Request $request, ProviderService $service): JsonResponse
    {
        $provider = $this->accountProvider($request->user());

        if (! $provider || (int) $service->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado.',
            ], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Ingresá el nombre del servicio.',
        ]);

        $service->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $service->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente.',
            'service' => $this->servicePayload($service),
        ]);
    }

    public function destroy(Request $request, ProviderService $service): JsonResponse
    {
        $provider = $this->accountProvider($request->user());

        if (! $provider || (int) $service->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado.',
            ], 404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente.',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $provider = $this->accountProvider($request->user());

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debés configurar tu perfil de proveedor.',
            ], 400);
        }

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'integer', 'exists:provider_services,id'],
        ]);

        foreach ($data['order'] as $index => $serviceId) {
            ProviderService::where('id', $serviceId)
                ->where('provider_id', $provider->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado.',
            'services' => $provider->services()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (ProviderService $service) => $this->servicePayload($service))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Grupos activos con sus subgrupos y los que ya ofrece la cuenta.
     */
    public function subgroups(Request $request): JsonResponse
    {
        $user = $request->user();

        $groups = Group::with(['subgroups' => function ($query) {
            $query->orderBy('description');
        }])
            ->active()
            ->orderBy('description')
            ->get();

        $provider = $user->provider ?: $this->accountProvider($user);

        return response()->json([
            'success' => true,
            'groups' => $groups
                ->map(fn (Group $group) => [
                    'id' => $group->id,
                    'description' => $group->description,
                    'subgroups' => $group->subgroups
                        ->map(fn ($subgroup) => [
                            'id' => $subgroup->id,
                            'description' => $subgroup->description,
                            'group_id' => $subgroup->group_id,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'selected' => $provider ? $provider->subgroups->pluck('id')->values()->all() : [],
        ]);
    }

    /**
     * Activa o desactiva un subgrupo del negocio de la cuenta.
     */
    public function toggleSubgroup(Request $request): JsonResponse
    {
        $user = $request->user();

        if (strcasecmp((string) $user->typeUser?->description, 'Prestador') !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los prestadores pueden configurar categorías.',
            ], 403);
        }

        $data = $request->validate([
            'subgroup_id' => ['required', 'integer', 'exists:sub_groups,id'],
        ], [
            'subgroup_id.required' => 'Seleccioná un subgrupo.',
            'subgroup_id.exists' => 'El subgrupo seleccionado no existe.',
        ]);

        $owner = $this->accountPrincipal($user) ?? $user;
        $provider = $owner->provider;

        if (! $provider) {
            $provider = Provider::create([
                'user_id' => $owner->id,
                'business_name' => trim((string) $owner->name) !== '' ? trim((string) $owner->name) : 'Mi negocio',
                'is_active' => true,
            ]);
        }

        $subgroupId = (int) $data['subgroup_id'];
        $exists = $provider->subgroups()->where('subgroup_id', $subgroupId)->exists();

        if ($exists) {
            $provider->subgroups()->detach($subgroupId);
            $message = 'Subgrupo removido correctamente.';
        } else {
            $provider->subgroups()->attach($subgroupId);
            $message = 'Subgrupo agregado correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'selected' => $provider->subgroups()->pluck('sub_groups.id')->values()->all(),
        ]);
    }

    /**
     * Tipos de servicio activos (urgencia, cita, presupuesto).
     */
    public function serviceTypes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasPageUrl('ordenes-servicio')) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
            ], 403);
        }

        $provider = $this->accountProvider($user);

        return response()->json([
            'success' => true,
            'service_types' => ServiceType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name', 'description'])
                ->map(fn (ServiceType $type) => [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'description' => $type->description,
                ])
                ->values()
                ->all(),
            'selected' => $provider ? $provider->serviceTypes()->pluck('service_types.id')->values()->all() : [],
        ]);
    }

    /**
     * Activa o desactiva un tipo de servicio del negocio de la cuenta.
     */
    public function toggleServiceType(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasPageUrl('ordenes-servicio')) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
            ], 403);
        }

        if (strcasecmp((string) $user->typeUser?->description, 'Prestador') !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los prestadores pueden configurar tipos de servicio.',
            ], 403);
        }

        $data = $request->validate([
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
        ], [
            'service_type_id.required' => 'Seleccioná un tipo de servicio.',
            'service_type_id.exists' => 'El tipo de servicio seleccionado no existe.',
        ]);

        $owner = $this->accountPrincipal($user) ?? $user;
        $provider = $owner->provider;

        if (! $provider) {
            $provider = Provider::create([
                'user_id' => $owner->id,
                'business_name' => trim((string) $owner->name) !== '' ? trim((string) $owner->name) : 'Mi negocio',
                'is_active' => true,
            ]);
        }

        $serviceTypeId = (int) $data['service_type_id'];
        $exists = $provider->serviceTypes()->where('service_type_id', $serviceTypeId)->exists();

        if ($exists) {
            $provider->serviceTypes()->detach($serviceTypeId);
            $message = 'Tipo de servicio removido correctamente.';
        } else {
            $provider->serviceTypes()->attach($serviceTypeId);
            $message = 'Tipo de servicio agregado correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'selected' => $provider->serviceTypes()->pluck('service_types.id')->values()->all(),
        ]);
    }

    private function servicePayload(ProviderService $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'sort_order' => $service->sort_order,
        ];
    }
}

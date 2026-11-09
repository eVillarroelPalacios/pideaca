<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderDriver;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Repartidores de confianza del negocio.
 *
 * Replica ProviderDispatchController (index/settings/store/destroy) sin tocar
 * la web: solo el dueño del comercio opera su lista y no se puede dejar la
 * modalidad activa sin integrantes.
 */
class ProfileDriversController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $provider = $this->currentProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'trusted_drivers_enabled' => (bool) $provider->trusted_drivers_enabled,
            ],
            'drivers' => $this->trustedDriversPayload($provider),
            'candidates' => $this->candidatesPayload($provider),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $provider = $this->currentProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $data['enabled'];

        if ($enabled && ! $provider->providerDrivers()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Agregá al menos un repartidor de confianza antes de activar la modalidad.',
            ], 422);
        }

        $provider->update(['trusted_drivers_enabled' => $enabled]);

        return response()->json([
            'success' => true,
            'message' => $enabled
                ? 'Modalidad activa: los pedidos solo los verán tus repartidores de confianza.'
                : 'Modalidad desactivada: los pedidos vuelven al pool general.',
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'trusted_drivers_enabled' => (bool) $provider->trusted_drivers_enabled,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $provider = $this->currentProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ], [
            'user_id.required' => 'Seleccioná un repartidor.',
            'user_id.exists' => 'El repartidor seleccionado no existe.',
        ]);

        $user = User::with('typeUser')->findOrFail((int) $data['user_id']);

        if (! $user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado no tiene el rol Repartidor.',
            ], 422);
        }

        $provider->providerDrivers()->updateOrCreate(
            ['user_id' => $user->id],
            ['status' => 'active']
        );

        return response()->json([
            'success' => true,
            'message' => $user->name.' se agregó a tus repartidores de confianza.',
            'drivers' => $this->trustedDriversPayload($provider),
            'candidates' => $this->candidatesPayload($provider),
        ]);
    }

    public function destroy(Request $request, ProviderDriver $driver): JsonResponse
    {
        $provider = $this->currentProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ((int) $driver->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ese repartidor no pertenece a tu lista de confianza.',
            ], 404);
        }

        $remainingActive = $provider->providerDrivers()
            ->where('status', 'active')
            ->where('id', '!=', $driver->id)
            ->count();

        $lastActive = $provider->trusted_drivers_enabled
            && $driver->status === 'active'
            && $remainingActive === 0;

        if ($lastActive && ! $request->boolean('disable_mode')) {
            return response()->json([
                'success' => false,
                'code' => 'last_active_driver',
                'message' => 'No podés quitar al último repartidor mientras la modalidad esté activa. Desactivá la modalidad o agregá otro repartidor.',
                'provider' => [
                    'id' => $provider->id,
                    'trusted_drivers_enabled' => true,
                ],
            ], 422);
        }

        if ($lastActive) {
            $provider->update(['trusted_drivers_enabled' => false]);
        }

        $name = $driver->user?->name ?: 'El repartidor';
        $driver->delete();

        $message = $name.' se quitó de tus repartidores de confianza.';

        if ($lastActive) {
            $message .= ' La modalidad quedó desactivada y los pedidos vuelven al pool general.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'provider' => [
                'id' => $provider->id,
                'trusted_drivers_enabled' => (bool) $provider->fresh()->trusted_drivers_enabled,
            ],
            'drivers' => $this->trustedDriversPayload($provider),
            'candidates' => $this->candidatesPayload($provider),
        ]);
    }

    private function currentProvider(Request $request): Provider|JsonResponse
    {
        $provider = $request->user()->provider()->first();

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
            ], 403);
        }

        return $provider;
    }

    private function trustedDriversPayload(Provider $provider): array
    {
        return $provider->providerDrivers()
            ->with(['user:id,name,email,phone,vehicle_type,vehicle_plate'])
            ->orderBy('id')
            ->get()
            ->map(function (ProviderDriver $link) {
                $user = $link->user;

                return [
                    'id' => $link->id,
                    'user_id' => $link->user_id,
                    'name' => $user?->name,
                    'email' => $user?->email,
                    'phone' => $user?->phone,
                    'vehicle' => $user?->vehicle_type,
                    'vehicle_label' => $user?->vehicleTypeLabel(),
                    'vehicle_plate' => $user?->vehicle_plate,
                    'status' => $link->status,
                ];
            })
            ->values()
            ->all();
    }

    private function candidatesPayload(Provider $provider): array
    {
        $linkedIds = $provider->providerDrivers()->pluck('user_id')->all();

        return User::query()
            ->whereHas('typeUser', fn ($query) => $query->whereRaw('LOWER(description) = ?', ['repartidor']))
            ->when($linkedIds !== [], fn ($query) => $query->whereNotIn('id', $linkedIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'vehicle_type', 'vehicle_plate'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'vehicle' => $user->vehicle_type,
                'vehicle_label' => $user->vehicleTypeLabel(),
                'vehicle_plate' => $user->vehicle_plate,
            ])
            ->values()
            ->all();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderDriver;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Despacho de pedidos con "repartidores de confianza".
 *
 * El comercio decide si sus pedidos se publican al pool general o si quedan
 * cerrados a su lista de repartidores. No existe ningun timer ni proceso en
 * segundo plano: un pedido solo sale al pool general cuando el comercio
 * desactiva la modalidad o usa la accion manual "Enviar al pool general".
 */
class ProviderDispatchController extends Controller
{
    /**
     * Estado de la modalidad, lista de confianza y repartidores disponibles
     * para agregar.
     */
    public function index(): JsonResponse
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

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

    /**
     * Activa o desactiva el despacho cerrado.
     *
     * Para activarlo se exige al menos un repartidor de confianza activo: un
     * pool cerrado sin integrantes dejaria los pedidos sin nadie que los vea.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

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

    /**
     * Agrega (o reactiva) un repartidor a la lista de confianza.
     */
    public function store(Request $request): JsonResponse
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
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

    /**
     * Quita un repartidor de la lista de confianza.
     *
     * Si es el ultimo integrante activo y la modalidad esta encendida, el
     * frontend envia "disable_mode=1": se apaga la modalidad y se quita al
     * repartidor en la misma operacion, con un mensaje explicito. Sin ese flag
     * la API responde 422 para que ningun cliente deje pedidos cerrados sin
     * nadie que pueda verlos.
     */
    public function destroy(Request $request, ProviderDriver $driver): JsonResponse
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        if ((int) $driver->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ese repartidor no pertenece a tu lista de confianza.',
            ], 404);
        }

        // No se puede dejar la modalidad activa sin integrantes: los pedidos
        // quedarian sin nadie que pueda verlos. La salida es explicita del
        // comercio (desactivar o agregar otro), nunca automatica por tiempo.
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

        try {
            if ($lastActive) {
                $provider->update(['trusted_drivers_enabled' => false]);
            }

            $name = $driver->user?->name ?: 'El repartidor';
            $driver->delete();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo quitar el repartidor. Intentá nuevamente en unos segundos.',
            ], 500);
        }

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

    /**
     * Accion manual del comercio: libera un pedido al pool general.
     *
     * No hay liberacion automatica por tiempo. Una vez liberado, cualquier
     * repartidor puede verlo y tomarlo aunque la modalidad siga activa.
     */
    public function sendOrderToPool(Order $order): JsonResponse
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        if ((int) $order->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ese pedido no pertenece a tu comercio.',
            ], 404);
        }

        if (! empty($order->driver_id)) {
            return response()->json([
                'success' => false,
                'message' => 'El pedido ya tiene un repartidor asignado.',
            ], 422);
        }

        if (! $order->isReleasedToPool()) {
            $order->update(['released_to_pool_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido enviado al pool general. Ya puede tomarlo cualquier repartidor.',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Integrantes activos de la lista de confianza con sus datos de contacto.
     */
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

    /**
     * Repartidores que todavia no estan en la lista, para el selector de alta.
     */
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

    private function currentProvider()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Se consulta la relacion (no la propiedad cacheada) para leer siempre
        // el estado actual del comercio, incluso dentro de la misma request.
        $provider = Auth::user()->provider()->first();

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
            ], 403);
        }

        return $provider;
    }
}

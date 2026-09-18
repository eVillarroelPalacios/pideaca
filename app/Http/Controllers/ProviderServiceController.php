<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Provider;
use App\Models\ProviderService;

class ProviderServiceController extends Controller
{
    private function getProvider()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return $user->provider;
    }

    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->getProvider();

        if (!$provider) {
            return response()->json(['success' => false, 'services' => []]);
        }

        $services = $provider->services()->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'services' => $services,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->getProvider();

        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Primero debés configurar tu perfil de proveedor.'], 400);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $maxOrder = ProviderService::where('provider_id', $provider->id)->max('sort_order');

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'name' => $request->name,
            'description' => $request->description,
            'sort_order' => ($maxOrder ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio agregado correctamente.',
            'service' => $service,
        ]);
    }

    public function update(Request $request, ProviderService $service)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->getProvider();

        if (!$provider || $service->provider_id !== $provider->id) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado.'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $service->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente.',
            'service' => $service,
        ]);
    }

    public function destroy(ProviderService $service)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->getProvider();

        if (!$provider || $service->provider_id !== $provider->id) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado.'], 404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente.',
        ]);
    }

    public function reorder(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->getProvider();

        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Primero debés configurar tu perfil de proveedor.'], 400);
        }

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:provider_services,id',
        ]);

        foreach ($request->order as $index => $serviceId) {
            ProviderService::where('id', $serviceId)
                ->where('provider_id', $provider->id)
                ->update(['sort_order' => $index + 1]);
        }

        $services = $provider->services()->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado.',
            'services' => $services,
        ]);
    }
}

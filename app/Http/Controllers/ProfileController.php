<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\Group;
use App\Models\SubGroup;

class ProfileController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user()->load(['status', 'typeUser', 'primaryAddress', 'provider.category', 'provider.services', 'provider.images']);

        return response()->json($user);
    }

    public function update(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'user' => Auth::user()->load(['status', 'typeUser']),
        ]);
    }

    public function updatePassword(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['La contraseña actual no es correcta.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    public function updateProvider(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (!$user->typeUser || $user->typeUser->description !== 'Prestador') {
            return response()->json(['success' => false, 'message' => 'Solo los prestadores pueden configurar perfil de proveedor.'], 403);
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'zone' => 'nullable|string|max:255',
            'promo' => 'nullable|string|max:500',
            'hours' => 'nullable|array',
        ]);

        $provider = $user->provider;

        if (!$provider) {
            $provider = Provider::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'description' => $request->description,
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp,
                'zone' => $request->zone,
                'promo' => $request->promo,
                'hours' => $request->hours,
                'is_active' => true,
            ]);
        } else {
            $provider->update([
                'business_name' => $request->business_name,
                'description' => $request->description,
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp,
                'zone' => $request->zone,
                'promo' => $request->promo,
                'hours' => $request->hours,
            ]);
        }

        $provider->load('category', 'services', 'images');

        return response()->json([
            'success' => true,
            'message' => 'Perfil de proveedor actualizado correctamente.',
            'provider' => $provider,
        ]);
    }

    public function storeImage(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (!$user->provider) {
            return response()->json(['success' => false, 'message' => 'Primero debés configurar tu perfil de proveedor.'], 400);
        }

        $request->validate([
            'image' => 'required|image|max:5120',
            'image_type' => 'required|string|in:banner,logo,gallery',
        ]);

        $file = $request->file('image');
        $filename = 'provider_' . $user->provider->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/providers', $filename);

        $maxOrder = ProviderImage::where('provider_id', $user->provider->id)
            ->where('image_type', $request->image_type)
            ->max('sort_order');

        $isFirst = !ProviderImage::where('provider_id', $user->provider->id)
            ->where('image_type', $request->image_type)
            ->exists();

        $image = ProviderImage::create([
            'provider_id' => $user->provider->id,
            'image_path' => 'providers/' . $filename,
            'image_type' => $request->image_type,
            'is_primary' => $isFirst,
            'sort_order' => ($maxOrder ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Imagen subida correctamente.',
            'image' => $image,
        ]);
    }

    public function destroyImage(ProviderImage $image)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (!$user->provider || $image->provider_id !== $user->provider->id) {
            return response()->json(['success' => false, 'message' => 'Imagen no encontrada.'], 404);
        }

        $fullPath = storage_path('app/' . $image->image_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.',
        ]);
    }

    public function getProviderSubgroups()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        $groups = Group::with(['subgroups' => function ($q) {
            $q->orderBy('description');
        }])->orderBy('description')->get();

        $providerSubgroupIds = [];
        if ($user->provider) {
            $providerSubgroupIds = $user->provider->subgroups->pluck('id')->toArray();
        }

        return response()->json([
            'success' => true,
            'groups' => $groups,
            'selected' => $providerSubgroupIds,
        ]);
    }

    public function toggleProviderSubgroup(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (!$user->provider) {
            return response()->json(['success' => false, 'message' => 'Primero debés configurar tu perfil de proveedor.'], 400);
        }

        $request->validate([
            'subgroup_id' => 'required|exists:sub_groups,id',
        ]);

        $provider = $user->provider;
        $subgroupId = $request->subgroup_id;

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
            'selected' => $provider->subgroups->pluck('id')->toArray(),
        ]);
    }

    public function getAddress()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user()->load(['primaryAddress.country', 'primaryAddress.province']);

        return response()->json([
            'address' => $user->primaryAddress,
        ]);
    }

    public function saveAddress(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'province_id' => 'nullable|exists:regions,id',
            'street' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'floor_apartment' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user = Auth::user();
        $address = $user->primaryAddress();

        $data = [
            'country_id' => $request->country_id,
            'province_id' => $request->province_id,
            'street' => $request->street,
            'number' => $request->number,
            'floor_apartment' => $request->floor_apartment,
            'postal_code' => $request->postal_code,
            'notes' => $request->notes,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_primary' => true,
        ];

        if ($address->exists()) {
            $address->update($data);
        } else {
            $user->addresses()->create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dirección guardada correctamente.',
            'address' => $user->load('primaryAddress.country', 'primaryAddress.province')->primaryAddress,
        ]);
    }

    public function getCountries()
    {
        $countries = \App\Models\Country::where('is_active', true)->orderBy('name')->get(['id', 'iso_code', 'name']);

        return response()->json($countries);
    }

    public function getRegions(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $regions = \App\Models\Region::where('country_id', $request->country_id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json($regions);
    }
}

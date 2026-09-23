<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Address;
use App\Models\Provider;
use App\Models\ProviderImage;

class UsuarioController extends Controller
{
    private function requireAuth()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        return null;
    }

    private function loadFull(User $user)
    {
        return $user->load([
            'status',
            'typeUser',
            'addresses.country',
            'addresses.province',
            'addresses.region',
            'primaryAddress.country',
            'primaryAddress.province',
            'primaryAddress.region',
            'provider.category',
            'provider.images',
            'pages.module',
        ]);
    }

    private function userJson(User $user)
    {
        return response()->json($this->loadFull($user));
    }

    public function index()
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $users = User::with(['status', 'typeUser', 'pages.module', 'primaryAddress.country', 'primaryAddress.province', 'primaryAddress.region', 'provider'])->orderBy('name')->get();

        return response()->json($users);
    }

    public function show(User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        return $this->userJson($user);
    }

    public function store(Request $request)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'user_status_id' => 'nullable|integer|exists:user_statuses,id',
            'type_user_id' => 'nullable|integer|exists:type_users,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_status_id' => $request->user_status_id,
            'type_user_id' => $request->type_user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'user' => $this->loadFull($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8',
            'user_status_id' => 'nullable|integer|exists:user_statuses,id',
            'type_user_id' => 'nullable|integer|exists:type_users,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'user_status_id' => $request->user_status_id,
            'type_user_id' => $request->type_user_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'user' => $this->loadFull($user),
        ]);
    }

    public function updateProvider(Request $request, User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'zone' => 'nullable|string|max:255',
            'promo' => 'nullable|string|max:500',
            'category_id' => 'nullable|integer|exists:groups,id',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'business_name' => $request->business_name,
            'description' => $request->description,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'zone' => $request->zone,
            'promo' => $request->promo,
            'category_id' => $request->category_id,
            'is_active' => (bool) $request->input('is_active', true),
        ];

        $provider = $user->provider;
        if ($provider) {
            $provider->update($data);
        } else {
            $data['user_id'] = $user->id;
            $provider = Provider::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Negocio actualizado correctamente.',
            'provider' => $provider->load('category'),
            'user' => $this->loadFull($user),
        ]);
    }

    public function storeAddress(Request $request, User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'province_id' => 'nullable|exists:regions,id',
            'street' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'floor_apartment' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
            'is_primary' => 'nullable|boolean',
        ]);

        $isPrimary = (bool) $request->input('is_primary', false);
        if ($isPrimary) {
            Address::where('user_id', $user->id)->update(['is_primary' => false]);
        }

        $user->addresses()->create([
            'country_id' => $request->country_id,
            'province_id' => $request->province_id,
            'street' => $request->street,
            'number' => $request->number,
            'floor_apartment' => $request->floor_apartment,
            'postal_code' => $request->postal_code,
            'notes' => $request->notes,
            'is_primary' => $isPrimary,
        ]);

        if (!$user->addresses()->where('is_primary', true)->exists()) {
            $first = $user->addresses()->orderBy('id')->first();
            if ($first) $first->update(['is_primary' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dirección creada correctamente.',
            'user' => $this->loadFull($user),
        ]);
    }

    public function updateAddress(Request $request, User $user, Address $address)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        if ($address->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Dirección no encontrada.'], 404);
        }

        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'province_id' => 'nullable|exists:regions,id',
            'street' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:20',
            'floor_apartment' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
            'is_primary' => 'nullable|boolean',
        ]);

        $isPrimary = (bool) $request->input('is_primary', false);
        if ($isPrimary) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_primary' => false]);
        }

        $address->update([
            'country_id' => $request->country_id,
            'province_id' => $request->province_id,
            'street' => $request->street,
            'number' => $request->number,
            'floor_apartment' => $request->floor_apartment,
            'postal_code' => $request->postal_code,
            'notes' => $request->notes,
            'is_primary' => $isPrimary,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección actualizada correctamente.',
            'user' => $this->loadFull($user),
        ]);
    }

    public function destroyAddress(User $user, Address $address)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        if ($address->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Dirección no encontrada.'], 404);
        }

        $wasPrimary = $address->is_primary;
        $address->delete();

        if ($wasPrimary) {
            $first = $user->addresses()->orderBy('id')->first();
            if ($first) $first->update(['is_primary' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dirección eliminada correctamente.',
            'user' => $this->loadFull($user),
        ]);
    }

    public function storeImage(Request $request, User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        if (!$user->provider) {
            return response()->json(['success' => false, 'message' => 'Primero creá el negocio del usuario.'], 400);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = 'provider_' . $user->provider->id . '.' . $ext;
        $destDir = public_path('images/publicidad');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $oldPath = $destDir . '/' . $filename;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        $file->move($destDir, $filename);
        $this->resizeImage($destDir . '/' . $filename, $ext, 200);

        ProviderImage::where('provider_id', $user->provider->id)
            ->where('image_type', 'publicidad')
            ->delete();

        $image = ProviderImage::create([
            'provider_id' => $user->provider->id,
            'image_path' => $filename,
            'image_type' => 'publicidad',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Imagen publicitaria subida correctamente.',
            'image' => $image,
            'user' => $this->loadFull($user),
        ]);
    }

    public function destroyImage(User $user, ProviderImage $image)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        if (!$user->provider || $image->provider_id !== $user->provider->id) {
            return response()->json(['success' => false, 'message' => 'Imagen no encontrada.'], 404);
        }

        $fullPath = public_path('images/publicidad/' . $image->image_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.',
            'user' => $this->loadFull($user),
        ]);
    }

    private function resizeImage(string $path, string $ext, int $maxWidth)
    {
        $info = @getimagesize($path);
        if (!$info) return;

        $origW = $info[0];
        $origH = $info[1];
        if ($origW <= $maxWidth) return;

        $newW = $maxWidth;
        $newH = (int) round($origH * ($maxWidth / $origW));

        $src = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            default => null,
        };
        if (!$src) return;

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $path, 85),
            'png' => imagepng($dst, $path, 6),
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    public function destroy(User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}

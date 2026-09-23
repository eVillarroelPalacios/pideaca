<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Address;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\Page;

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
        $loaded = $user->load([
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

        return $this->applyAccountFallbacks($loaded);
    }

    private function userJson(User $user)
    {
        return response()->json($this->loadFull($user));
    }

    public function index()
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $users = User::with([
            'status',
            'typeUser',
            'pages.module',
            'addresses.country',
            'addresses.province',
            'addresses.region',
            'primaryAddress.country',
            'primaryAddress.province',
            'primaryAddress.region',
            'provider.category',
            'provider.images',
        ])->orderBy('name')->get();

        foreach ($users as $user) {
            $this->applyAccountFallbacks($user);
        }

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
            'email_verified' => 'nullable|boolean',
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

        if ($request->has('email_verified')) {
            $data['email_verified_at'] = $request->boolean('email_verified') ? now() : null;
        }

        $user->fill($data);
        $user->save();

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
            'is_active' => (bool) $request->input('is_active', true),
        ];
        if ($request->has('category_id')) {
            $data['category_id'] = $request->category_id;
        }

        $provider = $user->provider ?: $this->resolveAccountProvider($user);
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

        $owner = $user;
        if (!$user->addresses()->exists()) {
            $principal = $this->resolveAccountPrincipal($user);
            if ($principal && (int) $principal->id !== (int) $user->id) {
                $owner = $principal;
            }
        }

        $isPrimary = (bool) $request->input('is_primary', false);
        if ($isPrimary) {
            Address::where('user_id', $owner->id)->update(['is_primary' => false]);
        }

        $owner->addresses()->create([
            'country_id' => $request->country_id,
            'province_id' => $request->province_id,
            'street' => $request->street,
            'number' => $request->number,
            'floor_apartment' => $request->floor_apartment,
            'postal_code' => $request->postal_code,
            'notes' => $request->notes,
            'is_primary' => $isPrimary,
        ]);

        if (!$owner->addresses()->where('is_primary', true)->exists()) {
            $first = $owner->addresses()->orderBy('id')->first();
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

        if (!$this->canManageAddress($user, $address)) {
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
            Address::where('user_id', $address->user_id)->where('id', '!=', $address->id)->update(['is_primary' => false]);
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

        if (!$this->canManageAddress($user, $address)) {
            return response()->json(['success' => false, 'message' => 'Dirección no encontrada.'], 404);
        }

        $ownerId = $address->user_id;
        $wasPrimary = $address->is_primary;
        $address->delete();

        if ($wasPrimary) {
            $first = Address::where('user_id', $ownerId)->orderBy('id')->first();
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

        $provider = $user->provider ?: $this->resolveAccountProvider($user);
        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Primero creá el negocio del usuario.'], 400);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = 'provider_' . $provider->id . '.' . $ext;
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

        ProviderImage::where('provider_id', $provider->id)
            ->where('image_type', 'publicidad')
            ->delete();

        $image = ProviderImage::create([
            'provider_id' => $provider->id,
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

        $provider = $user->provider ?: $this->resolveAccountProvider($user);
        if (!$provider || $image->provider_id !== $provider->id) {
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

    private function isPrincipalAccountUser(User $user): bool
    {
        $email = (string) $user->email;
        if ($email === '') {
            return true;
        }

        $minId = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->min('id');

        return (int) $user->id === (int) $minId;
    }

    private function applyPageToAccount(User $user, Page $page, bool $attach): int
    {
        $accountUsers = User::whereRaw('LOWER(email) = ?', [strtolower((string) $user->email)])->get();

        foreach ($accountUsers as $accountUser) {
            if ($attach) {
                $accountUser->pages()->syncWithoutDetaching([$page->id]);
            } else {
                $accountUser->pages()->detach($page->id);
            }
        }

        return $accountUsers->count();
    }

    public function attachPage(User $user, Page $page)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $user->pages()->syncWithoutDetaching([$page->id]);

        return response()->json([
            'success' => true,
            'message' => 'Página agregada al usuario.',
            'page_id' => $page->id,
            'cascaded' => false,
            'user' => $this->loadFull($user),
        ]);
    }

    public function detachPage(User $user, Page $page)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $cascaded = $this->isPrincipalAccountUser($user);

        if ($cascaded) {
            $count = $this->applyPageToAccount($user, $page, false);
            $message = $count > 1
                ? 'Página quitada del usuario principal y de ' . ($count - 1) . ' usuario(s) de la cuenta.'
                : 'Página quitada del usuario.';
        } else {
            $user->pages()->detach($page->id);
            $message = 'Página quitada del usuario.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'page_id' => $page->id,
            'cascaded' => $cascaded,
            'user' => $this->loadFull($user),
        ]);
    }

    private function resolveAccountPrincipal(User $user): ?User
    {
        $email = (string) $user->email;
        if ($email === '') {
            return null;
        }

        $principalId = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->min('id');
        if (!$principalId) {
            return null;
        }

        if ((int) $principalId === (int) $user->id) {
            return $user;
        }

        return User::find($principalId);
    }

    private function applyAccountFallbacks(User $user): User
    {
        $principal = $this->resolveAccountPrincipal($user);
        $isShared = $principal && (int) $principal->id !== (int) $user->id;

        if ($user->provider) {
            $user->provider->loadMissing(['category', 'images']);
        } elseif ($isShared && $principal->provider) {
            $provider = $principal->provider;
            $provider->load(['category', 'images']);
            $user->setRelation('provider', $provider);
            $user->setAttribute('provider_shared', true);
        }

        $hasAddresses = $user->relationLoaded('addresses') && $user->addresses->isNotEmpty();
        if (!$hasAddresses && $isShared) {
            $principal->loadMissing([
                'addresses.country',
                'addresses.province',
                'addresses.region',
                'primaryAddress.country',
                'primaryAddress.province',
                'primaryAddress.region',
            ]);
            if ($principal->addresses->isNotEmpty()) {
                $user->setRelation('addresses', $principal->addresses);
                $user->setAttribute('addresses_shared', true);
            }
            if ($principal->primaryAddress) {
                $user->setRelation('primaryAddress', $principal->primaryAddress);
                $user->setAttribute('primary_address_shared', true);
            }
        } elseif (!$user->primary_address && $isShared) {
            $principal->loadMissing([
                'primaryAddress.country',
                'primaryAddress.province',
                'primaryAddress.region',
            ]);
            if ($principal->primaryAddress) {
                $user->setRelation('primaryAddress', $principal->primaryAddress);
                $user->setAttribute('primary_address_shared', true);
            }
        }

        if (!$user->status && $isShared) {
            $principal->loadMissing('status');
            if ($principal->status) {
                $user->setRelation('status', $principal->status);
            }
        }

        if (!$user->typeUser && $isShared) {
            $principal->loadMissing('typeUser');
            if ($principal->typeUser) {
                $user->setRelation('typeUser', $principal->typeUser);
            }
        }

        return $user;
    }

    private function resolveAccountProvider(User $user): ?Provider
    {
        if ($user->provider) {
            return $user->provider;
        }

        $principal = $this->resolveAccountPrincipal($user);
        if (!$principal || (int) $principal->id === (int) $user->id) {
            return null;
        }

        return $principal->provider;
    }

    private function canManageAddress(User $user, Address $address): bool
    {
        if ($address->user_id === $user->id) {
            return true;
        }

        $principal = $this->resolveAccountPrincipal($user);

        return $principal && $address->user_id === $principal->id;
    }

    private function resolveProviderForSubgroups(User $user): ?Provider
    {
        return $this->resolveAccountProvider($user);
    }

    public function subgroups(User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $groups = \App\Models\Group::with(['subgroups' => function ($q) {
            $q->orderBy('description');
        }])->orderBy('description')->get();

        $provider = $this->resolveProviderForSubgroups($user);
        $selected = [];
        if ($provider) {
            $selected = $provider->subgroups()->pluck('sub_groups.id')->toArray();
        }

        return response()->json([
            'success' => true,
            'groups' => $groups,
            'selected' => $selected,
            'has_provider' => (bool) $provider,
        ]);
    }

    public function toggleSubgroup(Request $request, User $user)
    {
        if ($this->requireAuth()) return $this->requireAuth();

        $provider = $this->resolveProviderForSubgroups($user);
        if (!$provider) {
            return response()->json(['success' => false, 'message' => 'Este usuario no tiene negocio. Creá el negocio para asignar categorías.'], 400);
        }

        $request->validate([
            'subgroup_id' => 'required|exists:sub_groups,id',
        ]);

        $subgroupId = $request->input('subgroup_id');
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
            'selected' => $provider->subgroups()->pluck('sub_groups.id')->toArray(),
        ]);
    }
}

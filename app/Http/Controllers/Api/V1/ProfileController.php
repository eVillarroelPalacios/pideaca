<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesAccount;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * "Mi Perfil" para la app movil.
 *
 * Espeja resources/views/dashboard.blade.php (paneles y flags de visibilidad)
 * y las reglas de App\Http\Controllers\ProfileController sin tocar la web:
 * mismos modelos, mismas validaciones y mismos permisos por cuenta.
 */
class ProfileController extends Controller
{
    use ResolvesAccount;

    /**
     * Panel completo del usuario autenticado.
     *
     * Devuelve los flags que arma la vista web para decidir que paneles ve
     * cada tipo de usuario, mas el estado actual de empresa, direccion y
     * vehiculo para pintar los formularios sin llamadas extra.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'status',
            'typeUser',
            'primaryAddress.country',
            'primaryAddress.province',
            'provider.category',
            'provider.services',
            'provider.images',
            'provider.subgroups',
            'provider.serviceTypes',
        ]);

        $principal = $this->accountPrincipal($user);
        $isPrincipal = $user->isAccountPrincipal();
        $isAdmin = $user->isAdmin();
        $role = (string) ($user->typeUser?->description ?? '');
        $roleKey = strtolower(trim($role));

        $esPerfilPrestador = strcasecmp($role, 'Prestador') === 0;
        $esPerfilCliente = strcasecmp($role, 'Cliente') === 0;
        $esPerfilRepartidor = strcasecmp($role, 'Repartidor') === 0;

        $esRolNegocio = in_array($roleKey, ['prestador', 'comercio', 'comerciante', 'admin', 'administrador'], true);
        $canConfigureBusiness = (($esPerfilPrestador || $isAdmin) && ($isPrincipal || $isAdmin)) && $esRolNegocio;
        $canSeeAddress = $isPrincipal || ($user->typeUser && $user->hasPageUrl('direccion'));
        $canManageUsers = ($isAdmin || ! $esPerfilCliente)
            && ($isAdmin || $isPrincipal || $user->hasPageUrl('usuarios'));

        // Direccion propia o heredada del principal (solo lectura).
        $address = $user->primaryAddress;
        $addressShared = false;

        if (! $address && $principal && (int) $principal->id !== (int) $user->id) {
            $principal->load(['primaryAddress.country', 'primaryAddress.province']);

            if ($principal->primaryAddress) {
                $address = $principal->primaryAddress;
                $addressShared = true;
            }
        }

        $hasAddress = $address
            && trim((string) $address->street) !== ''
            && trim((string) $address->number) !== '';

        $initialPanel = $esPerfilPrestador
            ? 'negocio'
            : ($esPerfilCliente ? 'direccion' : ($esPerfilRepartidor ? 'vehiculo' : 'cuenta'));

        if (! $hasAddress && $isPrincipal) {
            $initialPanel = 'direccion';
        }

        if (! $isPrincipal && $initialPanel === 'direccion') {
            $initialPanel = 'cuenta';
        }

        if (! $canConfigureBusiness && in_array($initialPanel, ['negocio', 'horarios', 'servicios', 'repartidores', 'imagenes', 'categorias'], true)) {
            $initialPanel = 'cuenta';
        }

        $provider = $user->provider;

        return response()->json([
            'success' => true,
            'user' => $this->userPayload($user),
            'flags' => [
                'role' => $role,
                'is_admin' => $isAdmin,
                'is_principal' => $isPrincipal,
                'is_prestador' => $esPerfilPrestador,
                'is_cliente' => $esPerfilCliente,
                'is_repartidor' => $esPerfilRepartidor,
                'can_configure_business' => $canConfigureBusiness,
                'can_see_address' => $canSeeAddress,
                'can_manage_users' => $canManageUsers,
                'can_see_trusted_drivers' => $canConfigureBusiness && $provider !== null,
                'can_see_service_types' => $canConfigureBusiness && $user->hasPageUrl('ordenes-servicio'),
                'initial_panel' => $initialPanel,
            ],
            'provider' => $provider ? $this->providerPayload($provider) : null,
            'address' => $address ? $this->addressPayload($address) : null,
            'address_shared' => $addressShared,
            'vehicle_types' => collect(User::VEHICLE_TYPES)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'default_vehicle_type' => User::DEFAULT_VEHICLE_TYPE,
        ]);
    }

    /**
     * Nombre visible del usuario.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Ingresá tu nombre.',
        ]);

        $user = $request->user();
        $user->update(['name' => $data['name']]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'user' => $this->userPayload($user->fresh()->load(['status', 'typeUser'])),
        ]);
    }

    /**
     * Cambio de contraseña. La actual es opcional, igual que en la web.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Ingresá la nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = $request->user();

        if (! empty($data['current_password']) && ! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual no es correcta.',
                'errors' => ['current_password' => ['La contraseña actual no es correcta.']],
            ], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    /**
     * Datos del negocio y horarios (solo titular o Admin).
     */
    public function updateProvider(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAccountPrincipal() && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'La configuración del negocio la administra el usuario principal de la cuenta.',
            ], 403);
        }

        if (strcasecmp((string) $user->typeUser?->description, 'Prestador') !== 0 && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los prestadores pueden configurar perfil de proveedor.',
            ], 403);
        }

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'zone' => ['nullable', 'string', 'max:255'],
            'promo' => ['nullable', 'string', 'max:500'],
            'hours' => ['nullable', 'array'],
        ], [
            'business_name.required' => 'El nombre del negocio es obligatorio.',
        ]);

        $provider = $user->provider;

        if (! $provider) {
            $provider = Provider::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'description' => $data['description'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'zone' => $data['zone'] ?? null,
                'promo' => $data['promo'] ?? null,
                'hours' => $data['hours'] ?? null,
                'is_active' => true,
            ]);
        } else {
            $provider->update([
                'business_name' => $data['business_name'],
                'description' => $data['description'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'zone' => $data['zone'] ?? null,
                'promo' => $data['promo'] ?? null,
                'hours' => $data['hours'] ?? null,
            ]);
        }

        $provider->load(['category', 'services', 'images', 'subgroups', 'serviceTypes']);

        return response()->json([
            'success' => true,
            'message' => 'Perfil de proveedor actualizado correctamente.',
            'provider' => $this->providerPayload($provider),
        ]);
    }

    /**
     * Tipo de vehiculo y patente del repartidor autenticado.
     */
    public function updateVehicle(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('typeUser');

        if (! $user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los repartidores pueden actualizar su vehículo.',
            ], 403);
        }

        $data = $request->validate([
            'vehicle_type' => ['required', 'string', Rule::in(array_keys(User::VEHICLE_TYPES))],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
        ], [
            'vehicle_type.required' => 'Seleccioná un tipo de vehículo.',
            'vehicle_type.in' => 'El tipo de vehículo seleccionado no es válido.',
            'vehicle_plate.max' => 'La patente no puede superar los 20 caracteres.',
        ]);

        $attributes = ['vehicle_type' => $data['vehicle_type']];

        if ($request->exists('vehicle_plate')) {
            $plate = trim((string) $request->input('vehicle_plate'));
            $attributes['vehicle_plate'] = $plate !== '' ? mb_strtoupper($plate) : null;
        }

        $user->update($attributes);

        return response()->json([
            'success' => true,
            'vehicle_type' => $user->vehicle_type,
            'vehicle_plate' => $user->vehicle_plate,
            'label' => $user->vehicleTypeLabel(),
            'message' => 'Vehículo actualizado correctamente.',
        ]);
    }

    /**
     * Sube la imagen publicitaria (una sola por proveedor).
     */
    public function storeImage(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['status', 'typeUser']);

        if (! $user->canAdvertise()) {
            return response()->json([
                'success' => false,
                'message' => 'La publicidad está disponible solo para usuarios Prestador con estado Activo o Prueba.',
            ], 403);
        }

        if (! $user->provider) {
            return response()->json([
                'success' => false,
                'message' => 'Primero debés configurar tu perfil de proveedor.',
            ], 400);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'image.required' => 'Elegí una imagen para subir.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser JPG o PNG.',
            'image.max' => 'La imagen no puede superar los 5 MB.',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = 'provider_'.$user->provider->id.'.'.$ext;
        $destDir = public_path('images/publicidad');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $file->move($destDir, $filename);
        $this->resizeImage($destDir.'/'.$filename, $ext, 200);

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
            'message' => 'Publicidad subida correctamente.',
            'image' => $this->imagePayload($image),
        ]);
    }

    /**
     * Elimina una imagen publicitaria propia.
     */
    public function destroyImage(Request $request, ProviderImage $image): JsonResponse
    {
        $user = $request->user();

        if (! $user->provider || (int) $image->provider_id !== (int) $user->provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada.',
            ], 404);
        }

        $fullPath = public_path('images/publicidad/'.$image->image_path);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.',
        ]);
    }

    /**
     * Direccion propia o la del principal (shared=true, solo lectura).
     */
    public function address(Request $request): JsonResponse
    {
        $user = $request->user()->load(['primaryAddress.country', 'primaryAddress.province']);

        $address = $user->primaryAddress;
        $shared = false;

        if (! $address) {
            $principal = $this->accountPrincipal($user);

            if ($principal && (int) $principal->id !== (int) $user->id) {
                $principal->load(['primaryAddress.country', 'primaryAddress.province']);

                if ($principal->primaryAddress) {
                    $address = $principal->primaryAddress;
                    $shared = true;
                }
            }
        }

        return response()->json([
            'success' => true,
            'address' => $address ? $this->addressPayload($address) : null,
            'shared' => $shared,
        ]);
    }

    /**
     * Guarda la direccion principal (solo titular o Admin).
     */
    public function saveAddress(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAccountPrincipal() && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'La dirección la administra el usuario principal de la cuenta.',
            ], 403);
        }

        $data = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'province_id' => ['nullable', 'integer', 'exists:regions,id'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'floor_apartment' => ['nullable', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'country_id.required' => 'Seleccioná un país.',
            'country_id.exists' => 'El país seleccionado no existe.',
            'province_id.exists' => 'La provincia seleccionada no existe.',
        ]);

        $address = $user->primaryAddress();

        $changes = [
            'country_id' => $data['country_id'],
            'province_id' => $data['province_id'] ?? null,
            'street' => $data['street'] ?? null,
            'number' => $data['number'] ?? null,
            'floor_apartment' => $data['floor_apartment'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'notes' => $data['notes'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_primary' => true,
        ];

        if ($address->exists()) {
            $address->update($changes);
        } else {
            $user->addresses()->create($changes);
        }

        $user->load(['primaryAddress.country', 'primaryAddress.province']);

        return response()->json([
            'success' => true,
            'message' => 'Dirección guardada correctamente.',
            'address' => $user->primaryAddress ? $this->addressPayload($user->primaryAddress) : null,
            'shared' => false,
        ]);
    }

    public function countries(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Country::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'iso_code', 'name'])
                ->map(fn (Country $country) => [
                    'id' => $country->id,
                    'iso_code' => $country->iso_code,
                    'name' => $country->name,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function regions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
        ]);

        return response()->json([
            'success' => true,
            'data' => Region::where('country_id', $data['country_id'])
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn (Region $region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => $region->type,
                ])
                ->values()
                ->all(),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->typeUser?->description,
            'status' => $user->status?->status,
            'is_admin' => $user->isAdmin(),
            'is_principal' => $user->isAccountPrincipal(),
            'has_provider' => $user->provider()->exists(),
            'vehicle_type' => $user->vehicle_type,
            'vehicle_plate' => $user->vehicle_plate,
        ];
    }

    private function providerPayload(Provider $provider): array
    {
        $provider->loadMissing(['category', 'services', 'images', 'subgroups', 'serviceTypes']);

        return [
            'id' => $provider->id,
            'business_name' => $provider->business_name,
            'description' => $provider->description,
            'phone' => $provider->phone,
            'whatsapp' => $provider->whatsapp,
            'zone' => $provider->zone,
            'promo' => $provider->promo,
            'hours' => $provider->hours ?: (object) [],
            'is_active' => (bool) $provider->is_active,
            'trusted_drivers_enabled' => (bool) $provider->trusted_drivers_enabled,
            'category_id' => $provider->category_id,
            'category' => $provider->category ? [
                'id' => $provider->category->id,
                'description' => $provider->category->description,
            ] : null,
            'services' => $provider->services
                ->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'sort_order' => $service->sort_order,
                ])
                ->values()
                ->all(),
            'images' => $provider->images
                ->map(fn (ProviderImage $image) => $this->imagePayload($image))
                ->values()
                ->all(),
            'subgroup_ids' => $provider->subgroups->pluck('id')->values()->all(),
            'service_type_ids' => $provider->serviceTypes->pluck('id')->values()->all(),
        ];
    }

    private function imagePayload(ProviderImage $image): array
    {
        return [
            'id' => $image->id,
            'image_path' => $image->image_path,
            'image_type' => $image->image_type,
            'is_primary' => (bool) $image->is_primary,
            'sort_order' => $image->sort_order,
            'url' => asset('images/publicidad/'.$image->image_path),
        ];
    }

    private function addressPayload($address): array
    {
        return [
            'id' => $address->id,
            'country_id' => $address->country_id,
            'province_id' => $address->province_id,
            'street' => $address->street,
            'number' => $address->number,
            'floor_apartment' => $address->floor_apartment,
            'postal_code' => $address->postal_code,
            'notes' => $address->notes,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_primary' => (bool) $address->is_primary,
            'country' => $address->country ? [
                'id' => $address->country->id,
                'name' => $address->country->name,
            ] : null,
            'province' => $address->province ? [
                'id' => $address->province->id,
                'name' => $address->province->name,
            ] : null,
        ];
    }

    private function resizeImage(string $path, string $ext, int $maxWidth): void
    {
        $info = @getimagesize($path);

        if (! $info) {
            return;
        }

        $origW = $info[0];
        $origH = $info[1];

        if ($origW <= $maxWidth) {
            return;
        }

        $newW = $maxWidth;
        $newH = (int) round($origH * ($maxWidth / $origW));

        $src = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            'webp' => imagecreatefromwebp($path),
            default => null,
        };

        if (! $src) {
            return;
        }

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $path, 85),
            'png' => imagepng($dst, $path, 6),
            'webp' => imagewebp($dst, $path, 85),
        };

        imagedestroy($src);
        imagedestroy($dst);
    }
}

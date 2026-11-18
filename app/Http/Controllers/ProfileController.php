<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\DriverWithdrawalRequest;
use App\Models\Group;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderDriver;
use App\Models\ProviderImage;
use App\Models\Region;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\DriverWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user()->load(['status', 'typeUser', 'primaryAddress', 'provider.category', 'provider.services', 'provider.images']);

        return response()->json($user);
    }

    public function update(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Auth::user()->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'user' => Auth::user()->load(['status', 'typeUser']),
        ]);
    }

    public function updatePassword(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'current_password' => 'nullable|string',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $user = Auth::user();

        if ($request->filled('current_password') && ! Hash::check($request->current_password, $user->password)) {
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
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        // Solo el titular configura el negocio: el secundario hereda todo del principal.
        // El Admin omite esta validación.
        if (! $user->isAccountPrincipal() && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'La configuración del negocio la administra el usuario principal de la cuenta.',
            ], 403);
        }

        if (! $user->typeUser || $user->typeUser->description !== 'Prestador') {
            return response()->json(['success' => false, 'message' => 'Solo los prestadores pueden configurar perfil de proveedor.'], 403);
        }

        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'zone' => 'nullable|string|max:255',
            'promo' => 'nullable|string|max:500',
            'hours' => 'nullable|array',
            // Motor de tarifas de envio configurable por el comercio.
            'delivery_fee_mode' => 'nullable|string|in:global,flat,distance',
            'delivery_fee_flat' => 'nullable|numeric|min:0|max:999999',
            'delivery_fee_base' => 'nullable|numeric|min:0|max:999999',
            'delivery_fee_per_km' => 'nullable|numeric|min:0|max:999999',
            'delivery_fee_min' => 'nullable|numeric|min:0|max:999999',
            'delivery_fee_max' => 'nullable|numeric|min:0|max:999999',
        ]);

        $provider = $user->provider;

        $delivery = [];

        if ($request->hasAny([
            'delivery_fee_mode',
            'delivery_fee_flat',
            'delivery_fee_base',
            'delivery_fee_per_km',
            'delivery_fee_min',
            'delivery_fee_max',
        ])) {
            // Update parcial: los campos no enviados conservan el valor
            // guardado en vez de resetearse a 0/global.
            $delivery = [
                'delivery_fee_mode' => $data['delivery_fee_mode'] ?? $provider?->delivery_fee_mode ?? 'global',
                'delivery_fee_flat' => $request->has('delivery_fee_flat') ? (float) ($data['delivery_fee_flat'] ?? 0) : (float) ($provider?->delivery_fee_flat ?? 0),
                'delivery_fee_base' => $request->has('delivery_fee_base') ? (float) ($data['delivery_fee_base'] ?? 0) : (float) ($provider?->delivery_fee_base ?? 0),
                'delivery_fee_per_km' => $request->has('delivery_fee_per_km') ? (float) ($data['delivery_fee_per_km'] ?? 0) : (float) ($provider?->delivery_fee_per_km ?? 0),
                'delivery_fee_min' => $request->has('delivery_fee_min') ? (float) ($data['delivery_fee_min'] ?? 0) : (float) ($provider?->delivery_fee_min ?? 0),
                'delivery_fee_max' => $request->has('delivery_fee_max') ? (float) ($data['delivery_fee_max'] ?? 0) : (float) ($provider?->delivery_fee_max ?? 0),
            ];
        }

        if (! $provider) {
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
                ...$delivery,
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
                ...$delivery,
            ]);
        }

        $provider->load('category', 'services', 'images');

        return response()->json([
            'success' => true,
            'message' => 'Perfil de proveedor actualizado correctamente.',
            'provider' => $provider,
        ]);
    }

    /**
     * Retiros pendientes de los repartidores del comercio.
     * GET /profile/provider/withdrawals
     *
     * El comercio solo ve solicitudes de sus repartidores: los que tienen al
     * menos un pedido entregado para su comercio o están en su lista de
     * repartidores de confianza. Solo el titular (o el Admin) gestiona.
     */
    public function providerWithdrawals()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (! $user->isAccountPrincipal() && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Los retiros los administra el usuario principal de la cuenta.',
            ], 403);
        }

        if (! $user->typeUser || $user->typeUser->description !== 'Prestador') {
            return response()->json(['success' => false, 'message' => 'Solo los comercios pueden gestionar retiros de repartidores.'], 403);
        }

        $provider = $user->provider;

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'Primero debés configurar tu perfil de proveedor.'], 404);
        }

        $requests = DriverWithdrawalRequest::with('user:id,name,email')
            ->where('status', DriverWithdrawalRequest::STATUS_PENDING)
            ->whereIn('user_id', $this->providerWithdrawalDriverIds($provider))
            ->latest()
            ->get()
            ->map(fn (DriverWithdrawalRequest $withdrawal) => $this->providerWithdrawalPayload($withdrawal));

        return response()->json([
            'success' => true,
            'withdrawals' => $requests,
        ]);
    }

    /**
     * Tope de auto-aprobación de retiros del comercio.
     * GET /profile/provider/auto-approve
     */
    public function providerAutoApprove()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->providerForWithdrawals(Auth::user());

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'No tenés permisos para administrar retiros.'], 403);
        }

        return response()->json([
            'success' => true,
            'auto_approve_max' => $provider->auto_approve_max !== null ? (float) $provider->auto_approve_max : null,
        ]);
    }

    /**
     * Guarda el tope de auto-aprobación (0 o vacío = todo manual).
     * PUT /profile/provider/auto-approve
     */
    public function updateProviderAutoApprove(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = $this->providerForWithdrawals(Auth::user());

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'No tenés permisos para administrar retiros.'], 403);
        }

        $data = $request->validate([
            'auto_approve_max' => ['nullable', 'numeric', 'min:0'],
        ]);

        $provider->auto_approve_max = $data['auto_approve_max'] !== null ? (float) $data['auto_approve_max'] : null;
        $provider->save();

        return response()->json([
            'success' => true,
            'message' => 'Tope de auto-aprobación actualizado.',
            'auto_approve_max' => $provider->auto_approve_max !== null ? (float) $provider->auto_approve_max : null,
        ]);
    }

    /**
     * El comercio confirma que ya pagó el retiro al repartidor.
     * POST /profile/provider/withdrawals/{withdrawal}/approve
     */
    public function approveProviderWithdrawal(DriverWithdrawalRequest $withdrawal, DriverWalletService $walletService)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();
        $provider = $this->providerForWithdrawals($user);

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'No tenés permisos para administrar retiros.'], 403);
        }

        if (! in_array((int) $withdrawal->user_id, $this->providerWithdrawalDriverIds($provider), true)) {
            return response()->json(['success' => false, 'message' => 'Esa solicitud no es de un repartidor de tu comercio.'], 403);
        }

        try {
            $approved = $walletService->approve($withdrawal, $user);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'No se pudo aprobar el retiro.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Retiro aprobado correctamente.',
            'withdrawal' => $this->providerWithdrawalPayload($approved),
        ]);
    }

    /**
     * El comercio rechaza el retiro y el monto vuelve a la billetera.
     * POST /profile/provider/withdrawals/{withdrawal}/reject
     */
    public function rejectProviderWithdrawal(Request $request, DriverWithdrawalRequest $withdrawal, DriverWalletService $walletService)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $provider = $this->providerForWithdrawals($user);

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'No tenés permisos para administrar retiros.'], 403);
        }

        if (! in_array((int) $withdrawal->user_id, $this->providerWithdrawalDriverIds($provider), true)) {
            return response()->json(['success' => false, 'message' => 'Esa solicitud no es de un repartidor de tu comercio.'], 403);
        }

        try {
            $rejected = $walletService->reject($withdrawal, $user, $data['reason'] ?? null);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'No se pudo rechazar el retiro.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Retiro rechazado. El monto volvió a la billetera del repartidor.',
            'withdrawal' => $this->providerWithdrawalPayload($rejected),
        ]);
    }

    private function providerForWithdrawals(User $user): ?Provider
    {
        if (! $user->isAccountPrincipal() && ! $user->isAdmin()) {
            return null;
        }

        if (! $user->typeUser || $user->typeUser->description !== 'Prestador') {
            return null;
        }

        return $user->provider;
    }

    /**
     * IDs de los repartidores del comercio: con entregas para él o en su
     * lista de confianza.
     */
    private function providerWithdrawalDriverIds(Provider $provider): array
    {
        $delivered = Order::where('provider_id', $provider->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('driver_id')
            ->distinct()
            ->pluck('driver_id');

        $trusted = ProviderDriver::where('provider_id', $provider->id)->pluck('user_id');

        return $delivered->merge($trusted)->filter()->unique()->values()->all();
    }

    private function providerWithdrawalPayload(DriverWithdrawalRequest $withdrawal): array
    {
        return [
            'id' => $withdrawal->id,
            'user_id' => $withdrawal->user_id,
            'user' => $withdrawal->relationLoaded('user') && $withdrawal->user ? [
                'id' => $withdrawal->user->id,
                'name' => $withdrawal->user->name,
                'email' => $withdrawal->user->email,
            ] : null,
            'amount' => (float) $withdrawal->amount,
            'status' => $withdrawal->status,
            'method' => $withdrawal->method,
            'reference' => $withdrawal->reference,
            'notes' => $withdrawal->notes,
            'created_at' => $withdrawal->created_at?->toIso8601String(),
            'reviewed_at' => $withdrawal->reviewed_at?->toIso8601String(),
        ];
    }

    public function storeImage(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (! $user->canAdvertise()) {
            return response()->json([
                'success' => false,
                'message' => 'La publicidad está disponible solo para usuarios Prestador con estado Activo o Prueba.',
            ], 403);
        }

        if (! $user->provider) {
            return response()->json(['success' => false, 'message' => 'Primero debés configurar tu perfil de proveedor.'], 400);
        }

        $request->validate([
            'image' => 'required|image:mimes:jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = 'provider_'.$user->provider->id.'.'.$ext;
        $destDir = public_path('images/publicidad');
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $file->move($destDir, $filename);

        $fullPath = $destDir.'/'.$filename;
        $this->resizeImage($fullPath, $ext, 200);

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
            'image' => $image,
        ]);
    }

    public function destroyImage(ProviderImage $image)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (! $user->provider || $image->provider_id !== $user->provider->id) {
            return response()->json(['success' => false, 'message' => 'Imagen no encontrada.'], 404);
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

    public function getProviderSubgroups()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        // Solo se ofrecen grupos activos: los desactivados no aparecen en
        // "Categorias y servicios" (los grupos sin estado siguen visibles).
        $groups = Group::with(['subgroups' => function ($q) {
            $q->orderBy('description');
        }])
            ->active()
            ->orderBy('description')
            ->get();

        $providerSubgroupIds = [];
        $provider = $user->provider ?: $this->resolveAccountProvider($user);
        if ($provider) {
            $providerSubgroupIds = $provider->subgroups->pluck('id')->toArray();
        }

        return response()->json([
            'success' => true,
            'groups' => $groups,
            'selected' => $providerSubgroupIds,
        ]);
    }

    public function toggleProviderSubgroup(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (! $user->typeUser || strcasecmp((string) $user->typeUser->description, 'Prestador') !== 0) {
            return response()->json(['success' => false, 'message' => 'Solo los prestadores pueden configurar categorías.'], 403);
        }

        $request->validate([
            'subgroup_id' => 'required|exists:sub_groups,id',
        ]);

        // El secundario hereda el negocio del principal: se opera sobre el
        // proveedor de la cuenta para no crear negocios duplicados.
        $owner = $this->resolveAccountPrincipal($user) ?? $user;
        $provider = $owner->provider;

        // El prestador todavía no guardó "Datos del Negocio": lo damos de alta
        // para poder vincularle categorías desde el primer ingreso.
        if (! $provider) {
            $provider = Provider::create([
                'user_id' => $owner->id,
                'business_name' => trim((string) $owner->name) !== '' ? trim((string) $owner->name) : 'Mi negocio',
                'is_active' => true,
            ]);
        }

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

    public function getServiceTypes()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $types = ServiceType::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'description']);

        $selected = [];

        $accountProvider = $this->resolveAccountProvider(Auth::user());
        if ($accountProvider) {
            $selected = $accountProvider->serviceTypes()->pluck('service_types.id')->all();
        }

        return response()->json([
            'success' => true,
            'service_types' => $types,
            'selected' => $selected,
        ]);
    }

    public function toggleServiceType(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        if (! $user->typeUser || strcasecmp((string) $user->typeUser->description, 'Prestador') !== 0) {
            return response()->json(['success' => false, 'message' => 'Solo los prestadores pueden configurar tipos de servicio.'], 403);
        }

        $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
        ]);

        // Igual que en categorías: el secundario hereda el negocio del principal.
        $owner = $this->resolveAccountPrincipal($user) ?? $user;
        $provider = $owner->provider;

        // El prestador todavía no guardó "Datos del Negocio": lo damos de alta
        // para poder vincularle tipos de servicio desde el primer ingreso.
        if (! $provider) {
            $provider = Provider::create([
                'user_id' => $owner->id,
                'business_name' => trim((string) $owner->name) !== '' ? trim((string) $owner->name) : 'Mi negocio',
                'is_active' => true,
            ]);
        }

        $serviceTypeId = $request->service_type_id;

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
            'selected' => $provider->serviceTypes()->pluck('service_types.id')->all(),
        ]);
    }

    public function getAddress()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user()->load(['primaryAddress.country', 'primaryAddress.province']);

        // El secundario hereda la dirección del principal: si no tiene
        // dirección propia se devuelve la de la cuenta (solo lectura).
        $shared = false;
        $address = $user->primaryAddress;
        if (! $address) {
            $principal = $this->resolveAccountPrincipal($user);
            if ($principal && (int) $principal->id !== (int) $user->id) {
                $principal->load(['primaryAddress.country', 'primaryAddress.province']);
                if ($principal->primaryAddress) {
                    $address = $principal->primaryAddress;
                    $shared = true;
                }
            }
        }

        return response()->json([
            'address' => $address,
            'shared' => $shared,
        ]);
    }

    public function saveAddress(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // El secundario no configura dirección propia: pertenece al negocio padre.
        // El Admin omite esta validación.
        if (! Auth::user()->isAccountPrincipal() && ! Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'La dirección la administra el usuario principal de la cuenta.',
            ], 403);
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
        $countries = Country::where('is_active', true)->orderBy('name')->get(['id', 'iso_code', 'name']);

        return response()->json($countries);
    }

    public function getRegions(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $regions = Region::where('country_id', $request->country_id)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json($regions);
    }

    private function resolveAccountPrincipal(User $user): ?User
    {
        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return $user;
        }

        $principalId = User::whereRaw('LOWER(email) = ?', [$email])->min('id');
        if (! $principalId) {
            return $user;
        }

        if ((int) $principalId === (int) $user->id) {
            return $user;
        }

        return User::find($principalId);
    }

    private function resolveAccountProvider(User $user): ?Provider
    {
        if ($user->provider) {
            return $user->provider;
        }

        $principal = $this->resolveAccountPrincipal($user);
        if (! $principal || (int) $principal->id === (int) $user->id) {
            return null;
        }

        return $principal->provider;
    }

    private function resizeImage(string $path, string $ext, int $maxWidth)
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

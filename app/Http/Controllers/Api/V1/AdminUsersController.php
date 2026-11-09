<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesAccount;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\ProviderService;
use App\Models\ServiceType;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Gestion de usuarios de la cuenta para la app movil.
 *
 * Espeja la seccion "usuarios" (Gestion de Usuarios) del panel web sin
 * tocarla: mismo alcance (la cuenta propia; el Admin ve todos los usuarios
 * igual que el index web), mismas reglas (maximo 3 secundarios, el
 * secundario hereda rol y paginas del principal, inactivar quita paginas,
 * no podes inactivarte a vos mismo) y mismo permiso (pagina usuarios). El
 * Admin opera sin bloqueos de cuenta ni de principal, igual que en la web.
 */
class AdminUsersController extends Controller
{
    use ResolvesAccount;

    private const MAX_SECONDARIES = 3;

    /**
     * Usuarios con su estado, rol, direccion principal y paginas (toda la
     * plataforma para el Admin, la cuenta propia para el resto), mas el
     * catalogo de modulos/paginas que el actor puede asignar, los tipos y
     * estados para los filtros, y los limites de creacion.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        // Cuenta efectiva: la gestionada si el Admin la pide con
        // `managed_user_id` (igual que umManagedEmail en la web), si no la
        // propia. El Admin ve todos los usuarios (igual que el index web).
        $scope = $this->resolveManagedScope($request, $actor);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $users = $actor->isAdmin() && $scope === null ? $this->allUsers() : $this->accountUsers($scope);
        $ownAccount = $this->accountUsers($scope);
        $secondaryCount = max(0, $ownAccount->count() - 1);

        return response()->json([
            'success' => true,
            'account_email' => strtolower(trim((string) $scope->email)),
            'managed_user' => (int) $scope->id === (int) $actor->id ? null : [
                'id' => $scope->id,
                'name' => $scope->name,
                'email' => $scope->email,
            ],
            'actor' => [
                'id' => $actor->id,
                'is_admin' => $actor->isAdmin(),
            ],
            'data' => $users
                ->map(fn (User $user) => $this->userPayload($user, $actor))
                ->values()
                ->all(),
            'catalog' => $this->catalogPayload($actor),
            'filters' => [
                'user_statuses' => UserStatus::orderBy('status')
                    ->get(['id', 'status'])
                    ->map(fn (UserStatus $status) => [
                        'id' => $status->id,
                        'status' => $status->status,
                    ])
                    ->values()
                    ->all(),
                'types' => TypeUser::orderBy('description')
                    ->get(['id', 'description'])
                    ->map(fn (TypeUser $type) => [
                        'id' => $type->id,
                        'description' => $type->description,
                    ])
                    ->values()
                    ->all(),
            ],
            'limits' => [
                'max_secondary' => self::MAX_SECONDARIES,
                'secondary_count' => $secondaryCount,
                'can_add' => $secondaryCount < self::MAX_SECONDARIES,
            ],
        ]);
    }

    /**
     * Alta de un usuario secundario en la cuenta.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        // La creacion respeta la cuenta efectiva (gestionada o propia).
        $scope = $this->resolveManagedScope($request, $actor);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $principal = $this->accountPrincipal($scope) ?? $scope;

        $accountTotal = User::whereRaw('LOWER(email) = ?', [strtolower(trim((string) $scope->email))])->count();

        if ($accountTotal > 0 && ($accountTotal - 1) >= self::MAX_SECONDARIES) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuario principal ya tiene el máximo de 3 usuarios secundarios.',
                'code' => 'ACCOUNT_USER_LIMIT',
            ], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'user_status_id' => ['nullable', 'integer', 'exists:user_statuses,id'],
        ], [
            'name.required' => 'Ingresá el nombre del usuario.',
            'password.required' => 'Ingresá una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'user_status_id.exists' => 'El estado seleccionado no existe.',
        ]);

        $statusId = $data['user_status_id']
            ?? UserStatus::whereRaw('LOWER(status) = ?', ['activo'])->value('id');

        $user = DB::transaction(function () use ($data, $principal, $statusId) {
            return User::create([
                'name' => $data['name'],
                'email' => strtolower(trim((string) $principal->email)),
                'password' => Hash::make($data['password']),
                'user_status_id' => $statusId,
                'type_user_id' => $principal->type_user_id,
            ]);
        });

        // El secundario nace con las paginas directas del principal.
        $inherited = $principal->pages()->pluck('pages.id')->all();

        if ($inherited !== []) {
            $user->pages()->syncWithoutDetaching($inherited);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'user' => $this->userPayload($user->fresh(), $actor),
        ], 201);
    }

    /**
     * Edicion de los datos del usuario (nombre, estado y rol del principal).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        if ($error = $this->denyUnlessAccountUser($actor, $user)) {
            return $error;
        }

        if ($user->isAccountPrincipal() && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario principal no puede modificarse desde esta pantalla.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'user_status_id' => ['sometimes', 'nullable', 'integer', 'exists:user_statuses,id'],
            'type_user_id' => ['sometimes', 'nullable', 'integer', 'exists:type_users,id'],
        ], [
            'name.required' => 'Ingresá el nombre del usuario.',
            'email.email' => 'Ingresá un email válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'user_status_id.exists' => 'El estado seleccionado no existe.',
            'type_user_id.exists' => 'El rol seleccionado no existe.',
        ]);

        // Actualizacion parcial: solo cambian los campos enviados (igual que
        // el panel de cuenta del perfil movil).
        $changes = ['name' => $data['name']];

        if ($request->exists('email') && $data['email'] !== null) {
            $changes['email'] = $data['email'];
        }

        if ($request->exists('user_status_id')) {
            $changes['user_status_id'] = $data['user_status_id'] ?? null;
        }

        if ($request->exists('type_user_id')) {
            $changes['type_user_id'] = $data['type_user_id'] ?? null;
        }

        $user->update($changes);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'user' => $this->userPayload($user->fresh(), $actor),
        ]);
    }

    /**
     * Inactiva al usuario y le quita las paginas asignadas.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        if ($error = $this->denyUnlessAccountUser($actor, $user)) {
            return $error;
        }

        if ((int) $user->id === (int) $actor->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes inactivar tu propia cuenta.',
            ], 422);
        }

        $status = UserStatus::whereRaw('LOWER(status) IN (?, ?)', ['inactivo', 'inactive'])->first()
            ?? UserStatus::create(['status' => 'Inactivo']);

        DB::transaction(function () use ($user, $status) {
            $user->user_status_id = $status->id;
            $user->save();
            $user->pages()->detach();
        });

        return response()->json([
            'success' => true,
            'message' => 'Usuario inactivado correctamente.',
        ]);
    }

    public function attachPage(Request $request, User $user, Page $page): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        if ($error = $this->denyUnlessAccountUser($actor, $user)) {
            return $error;
        }

        if ($user->isAccountPrincipal() && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario principal no puede modificarse desde esta pantalla.',
            ], 403);
        }

        if ($error = $this->denyUnlessAllowedPage($actor, $page)) {
            return $error;
        }

        $user->pages()->syncWithoutDetaching([$page->id]);

        return response()->json([
            'success' => true,
            'message' => 'Página agregada al usuario.',
            'page_id' => $page->id,
            'cascaded' => false,
            'user' => $this->userPayload($user->fresh(), $actor),
        ]);
    }

    public function detachPage(Request $request, User $user, Page $page): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        if ($error = $this->denyUnlessAccountUser($actor, $user)) {
            return $error;
        }

        if ($user->isAccountPrincipal() && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario principal no puede modificarse desde esta pantalla.',
            ], 403);
        }

        if ($error = $this->denyUnlessAllowedPage($actor, $page)) {
            return $error;
        }

        // Quitarle una pagina al principal la quita en toda la cuenta.
        $cascaded = $user->isAccountPrincipal();

        if ($cascaded) {
            User::whereRaw('LOWER(email) = ?', [strtolower(trim((string) $user->email))])
                ->get()
                ->each(function (User $accountUser) use ($page) {
                    $accountUser->pages()->detach($page->id);
                });

            $message = 'Página quitada del usuario principal y de la cuenta.';
        } else {
            $user->pages()->detach($page->id);
            $message = 'Página quitada del usuario.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'page_id' => $page->id,
            'cascaded' => $cascaded,
            'user' => $this->userPayload($user->fresh(), $actor),
        ]);
    }

    /**
     * Alta de la direccion principal del usuario (la web la guarda junto
     * con los datos en el modal de edicion).
     */
    public function storeAddress(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        if ($error = $this->denyUnlessAccountUser($actor, $user)) {
            return $error;
        }

        if ($user->isAccountPrincipal() && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario principal no puede modificarse desde esta pantalla.',
            ], 403);
        }

        $data = $this->validatedAddress($request);

        // Sin direcciones propias se usa la del principal, igual que en la web.
        $owner = $user->addresses()->exists()
            ? $user
            : ($this->accountPrincipal($user) ?? $user);

        $owner->addresses()->create($data + ['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección guardada correctamente.',
            'user' => $this->userPayload($user->fresh(), $actor),
        ]);
    }

    /**
     * Edicion de la direccion principal del usuario.
     */
    public function updateAddress(Request $request, User $user, Address $address): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();

        if ($error = $this->denyUnlessAccountUser($actor, $user)) {
            return $error;
        }

        if ($user->isAccountPrincipal() && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario principal no puede modificarse desde esta pantalla.',
            ], 403);
        }

        if (! $this->canManageAddress($user, $address)) {
            return response()->json([
                'success' => false,
                'message' => 'Dirección no encontrada.',
            ], 404);
        }

        $data = $this->validatedAddress($request);

        $address->update($data + ['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Dirección actualizada correctamente.',
            'user' => $this->userPayload($user->fresh(), $actor),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Perfil del usuario gestionado (modo managed de la web)
    |--------------------------------------------------------------------------
    | La web opera el Mi Perfil del gestionado con /admin/users/{id} y sus
    | sub-recursos (negocio, servicios, publicidad, categorias, tipos). Aca
    | se espeja con las mismas reglas; solo el Admin puede usarlo.
    */

    /**
     * Perfil completo del gestionado con el mismo formato que /profile.
     */
    public function profile(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $user->load([
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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $role,
                'status' => $user->status?->status,
                'is_admin' => $isAdmin,
                'is_principal' => $isPrincipal,
                'has_provider' => $user->provider()->exists(),
                'vehicle_type' => $user->vehicle_type,
                'vehicle_plate' => $user->vehicle_plate,
            ],
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
            'provider' => $provider ? $this->managedProviderPayload($provider) : null,
            'address' => $address ? $this->managedAddressPayload($address) : null,
            'address_shared' => $addressShared,
            'vehicle_types' => collect(User::VEHICLE_TYPES)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'default_vehicle_type' => User::DEFAULT_VEHICLE_TYPE,
        ]);
    }

    /**
     * Negocio y horarios del gestionado.
     */
    public function updateProvider(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'zone' => ['nullable', 'string', 'max:255'],
            'promo' => ['nullable', 'string', 'max:500'],
            'hours' => ['nullable', 'array'],
            'category_id' => ['nullable', 'integer', 'exists:groups,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'business_name.required' => 'Ingresá el nombre del negocio.',
        ]);

        $changes = [
            'business_name' => $data['business_name'],
            'description' => $data['description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'zone' => $data['zone'] ?? null,
            'promo' => $data['promo'] ?? null,
            'hours' => $request->input('hours'),
            'is_active' => (bool) $request->input('is_active', true),
        ];

        if ($request->has('category_id')) {
            $changes['category_id'] = $data['category_id'];
        }

        $provider = $user->provider ?: $this->accountProvider($user);

        if ($provider) {
            $provider->update($changes);
        } else {
            $provider = Provider::create($changes + ['user_id' => $user->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Negocio actualizado correctamente.',
            'provider' => $this->managedProviderPayload($provider->fresh()),
        ]);
    }

    /**
     * Servicios del negocio del gestionado.
     */
    public function services(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $provider = $this->resolveServiceProvider($user);

        return response()->json([
            'success' => true,
            'services' => $provider ? $provider->services()->orderBy('sort_order')->get()
                ->map(fn (ProviderService $service) => $this->managedServicePayload($service))
                ->values()
                ->all() : [],
        ]);
    }

    public function storeService(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $provider = $this->resolveServiceProvider($user);

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuario no tiene negocio. Creá el negocio para agregar servicios.',
            ], 400);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Ingresá el nombre del servicio.',
        ]);

        $maxOrder = ProviderService::where('provider_id', $provider->id)->max('sort_order');

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'name' => $data['name'],
            'description' => $data['description'],
            'sort_order' => ($maxOrder ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio agregado correctamente.',
            'service' => $this->managedServicePayload($service),
        ], 201);
    }

    public function updateService(Request $request, User $user, ProviderService $service): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $provider = $this->resolveServiceProvider($user);

        if (! $provider || (int) $service->provider_id !== (int) $provider->id) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado.'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Ingresá el nombre del servicio.',
        ]);

        $service->update([
            'name' => $data['name'],
            'description' => $request->input('description', $service->description),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente.',
            'service' => $this->managedServicePayload($service),
        ]);
    }

    public function destroyService(Request $request, User $user, ProviderService $service): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $provider = $this->resolveServiceProvider($user);

        if (! $provider || (int) $service->provider_id !== (int) $provider->id) {
            return response()->json(['success' => false, 'message' => 'Servicio no encontrado.'], 404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente.',
        ]);
    }

    /**
     * Publicidad del negocio del gestionado (una sola imagen).
     */
    public function storeImage(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        if (! $user->canAdvertise()) {
            return response()->json([
                'success' => false,
                'message' => 'La publicidad está disponible solo para usuarios Prestador con estado Activo o Prueba.',
            ], 403);
        }

        $provider = $user->provider ?: $this->accountProvider($user);

        if (! $provider) {
            return response()->json(['success' => false, 'message' => 'Primero creá el negocio del usuario.'], 400);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = 'provider_'.$provider->id.'.'.$ext;
        $destDir = public_path('images/publicidad');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $oldPath = $destDir.'/'.$filename;

        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        $file->move($destDir, $filename);
        $this->resizePublicityImage($destDir.'/'.$filename, $ext, 200);

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
            'message' => 'Publicidad subida correctamente.',
            'image' => $this->managedImagePayload($image),
        ], 201);
    }

    public function destroyImage(Request $request, User $user, ProviderImage $image): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $provider = $user->provider ?: $this->accountProvider($user);

        if (! $provider || (int) $image->provider_id !== (int) $provider->id) {
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

    /**
     * Categorias (subgrupos) del negocio del gestionado.
     */
    public function subgroups(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $groups = Group::with(['subgroups' => fn ($query) => $query->orderBy('description')])
            ->orderBy('description')
            ->get();

        $provider = $this->resolveServiceProvider($user);

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
            'selected' => $provider ? $provider->subgroups()->pluck('sub_groups.id')->toArray() : [],
        ]);
    }

    public function toggleSubgroup(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $provider = $this->resolveServiceProvider($user);

        if (! $provider) {
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

    /**
     * Tipos de servicio del negocio del gestionado.
     */
    public function serviceTypes(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $types = ServiceType::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'description']);

        $provider = $this->resolveServiceProvider($user);
        $selected = $provider ? $provider->serviceTypes()->pluck('service_types.id')->all() : [];

        return response()->json([
            'success' => true,
            'service_types' => $types,
            'selected' => $selected,
        ]);
    }

    public function toggleServiceType(Request $request, User $user): JsonResponse
    {
        if ($error = $this->denyUnlessManaged($request)) {
            return $error;
        }

        $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
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

        $serviceTypeId = $request->input('service_type_id');
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

    /**
     * Permiso identico al guard web `page:usuarios`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('usuarios')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    private function denyUnlessAccountUser(User $actor, User $target): ?JsonResponse
    {
        // El Admin gestiona cualquier usuario, igual que en la web.
        if ($actor->isAdmin()) {
            return null;
        }

        if (in_array((int) $target->id, array_map('intval', $this->accountUserIds($actor)), true)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Usuario no encontrado.',
        ], 404);
    }

    /**
     * Los endpoints del perfil gestionado son solo para el Admin (en la web
     * solo el Admin puede fijar un usuario gestionado).
     */
    private function denyUnlessManaged(Request $request): ?JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if (! $request->user()?->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede operar el perfil de otro usuario.',
            ], 403);
        }

        return null;
    }

    private function resolveServiceProvider(User $user): ?Provider
    {
        if ($user->provider) {
            return $user->provider;
        }

        return $this->accountProvider($user);
    }

    private function managedServicePayload(ProviderService $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'sort_order' => $service->sort_order,
        ];
    }

    private function managedImagePayload(ProviderImage $image): array
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

    private function managedProviderPayload(Provider $provider): array
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
                ->map(fn ($service) => $this->managedServicePayload($service))
                ->values()
                ->all(),
            'images' => $provider->images
                ->map(fn (ProviderImage $image) => $this->managedImagePayload($image))
                ->values()
                ->all(),
            'subgroup_ids' => $provider->subgroups->pluck('id')->values()->all(),
            'service_type_ids' => $provider->serviceTypes->pluck('id')->values()->all(),
        ];
    }

    private function managedAddressPayload(Address $address): array
    {
        $address->loadMissing(['country', 'province']);

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

    private function resizePublicityImage(string $path, string $ext, int $maxWidth): void
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
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    private function denyUnlessAllowedPage(User $actor, Page $page): ?JsonResponse
    {
        if ($actor->isAdmin()) {
            return null;
        }

        $allowed = $this->allowedModules($actor)
            ->flatMap(fn (Module $module) => $module->pages->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->all();

        if (in_array((int) $page->id, $allowed, true)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'No podés asignar una página que no tenés habilitada.',
        ], 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'province_id' => ['nullable', 'integer', 'exists:regions,id'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
        ], [
            'country_id.required' => 'Seleccioná un país.',
            'country_id.exists' => 'El país seleccionado no existe.',
            'province_id.exists' => 'La provincia seleccionada no existe.',
        ]);
    }

    private function canManageAddress(User $user, Address $address): bool
    {
        if ((int) $address->user_id === (int) $user->id) {
            return true;
        }

        $principal = $this->accountPrincipal($user);

        return $principal && (int) $address->user_id === (int) $principal->id;
    }

    private function accountUsers(User $actor): Collection
    {
        return User::with(['status', 'typeUser', 'pages.module', 'addresses', 'primaryAddress'])
            ->whereRaw('LOWER(email) = ?', [strtolower(trim((string) $actor->email))])
            ->orderBy('id')
            ->get();
    }

    /**
     * Cuenta efectiva del listado: el usuario gestionado si el Admin lo pide
     * con `managed_user_id` (igual que umManagedEmail en la web), si no el
     * propio actor. Devuelve la respuesta de error (403/404) si corresponde.
     */
    private function resolveManagedScope(Request $request, User $actor)
    {
        $managedId = $request->query('managed_user_id');

        if ($managedId === null || $managedId === '') {
            return $actor;
        }

        if (! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede gestionar otra cuenta.',
            ], 403);
        }

        $managed = User::find($managedId);

        if (! $managed) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return $managed;
    }

    private function allUsers(): Collection
    {
        return User::with(['status', 'typeUser', 'pages.module', 'addresses', 'primaryAddress'])
            ->orderBy('name')
            ->get();
    }

    private function allowedModules(User $actor): Collection
    {
        if ($actor->isAdmin()) {
            return Module::with(['pages' => fn ($query) => $query->orderBy('description')])
                ->orderBy('description')
                ->get();
        }

        return Module::with(['pages' => function ($query) use ($actor) {
            $query->whereHas('users', fn ($query) => $query->where('users.id', $actor->id))
                ->orderBy('description');
        }])
            ->orderBy('description')
            ->get()
            ->filter(fn (Module $module) => $module->pages->isNotEmpty())
            ->values();
    }

    private function catalogPayload(User $actor): array
    {
        return [
            'modules' => $this->allowedModules($actor)
                ->map(fn (Module $module) => [
                    'id' => $module->id,
                    'description' => $module->description,
                    'pages' => $module->pages
                        ->map(fn (Page $page) => [
                            'id' => $page->id,
                            'description' => $page->description,
                            'url' => $page->url,
                            'module_id' => $page->module_id,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function effectivePrimaryAddress(User $user): ?Address
    {
        $addresses = $user->addresses;

        $own = $user->primaryAddress
            ?? $addresses->firstWhere('is_primary', true)
            ?? $addresses->first();

        if ($own) {
            return $own;
        }

        // Sin direcciones propias se muestra la del principal, igual que en la web.
        $principal = $this->accountPrincipal($user);

        if (! $principal || (int) $principal->id === (int) $user->id) {
            return null;
        }

        $principal->loadMissing(['addresses', 'primaryAddress']);

        return $principal->primaryAddress
            ?? $principal->addresses->firstWhere('is_primary', true)
            ?? $principal->addresses->first();
    }

    private function userPayload(User $user, User $actor): array
    {
        $user->loadMissing(['status', 'typeUser', 'pages.module', 'addresses', 'primaryAddress']);

        $principal = $this->accountPrincipal($user) ?? $user;
        $principal->loadMissing('typeUser');

        $address = $this->effectivePrimaryAddress($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->typeUser?->description,
            'status' => $user->status?->status,
            'status_id' => $user->user_status_id,
            'type_user_id' => $user->type_user_id,
            'principal_role' => $principal->typeUser?->description,
            'is_principal' => $user->isAccountPrincipal(),
            'is_self' => (int) $user->id === (int) $actor->id,
            'is_inactive' => (bool) preg_match('/inactiv|inactive/i', (string) ($user->status?->status ?? '')),
            'can_edit' => ! $user->isAccountPrincipal() || $actor->isAdmin(),
            'page_ids' => $user->pages->pluck('id')->values()->all(),
            'pages' => $user->pages
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'description' => $page->description,
                    'url' => $page->url,
                    'module' => $page->module?->description,
                ])
                ->values()
                ->all(),
            'primary_address' => $address ? [
                'id' => $address->id,
                'country_id' => $address->country_id,
                'province_id' => $address->province_id,
                'street' => $address->street,
                'number' => $address->number,
            ] : null,
        ];
    }
}

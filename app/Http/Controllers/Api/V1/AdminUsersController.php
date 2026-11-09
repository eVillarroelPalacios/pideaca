<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesAccount;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Module;
use App\Models\Page;
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
 * tocarla: mismo alcance (usuarios que comparten el email de la cuenta),
 * mismas reglas (maximo 3 secundarios, el secundario hereda rol y paginas
 * del principal, inactivar quita paginas, no podes inactivarte a vos mismo)
 * y mismo permiso (pagina usuarios). El Admin opera sin bloqueos sobre el
 * principal, igual que en la web.
 */
class AdminUsersController extends Controller
{
    use ResolvesAccount;

    private const MAX_SECONDARIES = 3;

    /**
     * Usuarios de la cuenta con su estado, rol, direccion principal y
     * paginas, mas el catalogo de modulos/paginas que el actor puede
     * asignar y los limites de creacion.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $actor = $request->user();
        $users = $this->accountUsers($actor);
        $secondaryCount = max(0, $users->count() - 1);

        return response()->json([
            'success' => true,
            'account_email' => strtolower(trim((string) $actor->email)),
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
        $principal = $this->accountPrincipal($actor) ?? $actor;

        $accountTotal = User::whereRaw('LOWER(email) = ?', [strtolower(trim((string) $actor->email))])->count();

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
            'email' => ['required', 'email', 'max:255'],
            'user_status_id' => ['nullable', 'integer', 'exists:user_statuses,id'],
            'type_user_id' => ['nullable', 'integer', 'exists:type_users,id'],
        ], [
            'name.required' => 'Ingresá el nombre del usuario.',
            'email.required' => 'Ingresá el email del usuario.',
            'email.email' => 'Ingresá un email válido.',
            'user_status_id.exists' => 'El estado seleccionado no existe.',
            'type_user_id.exists' => 'El rol seleccionado no existe.',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_status_id' => $data['user_status_id'],
            'type_user_id' => $data['type_user_id'],
        ]);

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
        if (in_array((int) $target->id, array_map('intval', $this->accountUserIds($actor)), true)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Usuario no encontrado.',
        ], 404);
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

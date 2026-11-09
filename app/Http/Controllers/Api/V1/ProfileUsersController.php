<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesAccount;
use App\Http\Controllers\Controller;
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
 * Usuarios de la cuenta y sus permisos de paginas.
 *
 * La web usa /admin/users (listado global). En la app el alcance es la cuenta
 * del usuario autenticado (mismo email), con las mismas reglas: maximo 3
 * secundarios, el rol y las paginas del principal se heredan al crear, el
 * principal solo lo puede tocar un Admin y quitarte a vos mismo no se permite.
 */
class ProfileUsersController extends Controller
{
    use ResolvesAccount;

    private const MAX_SECONDARIES = 3;

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
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'user_status_id' => ['nullable', 'integer', 'exists:user_statuses,id'],
        ], [
            'name.required' => 'Ingresá el nombre del usuario.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'user_status_id.exists' => 'El estado seleccionado no existe.',
        ]);

        $changes = ['name' => $data['name']];

        if ($request->exists('user_status_id')) {
            $changes['user_status_id'] = $data['user_status_id'] ?? null;
        }

        if (! empty($data['password'])) {
            $changes['password'] = Hash::make($data['password']);
        }

        $user->update($changes);

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

        if ($user->isAccountPrincipal() && ! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario principal no puede modificarse desde esta pantalla.',
            ], 403);
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

        $cascaded = $user->isAccountPrincipal();

        if ($cascaded) {
            User::whereRaw('LOWER(email) = ?', [strtolower(trim((string) $user->email))])
                ->get()
                ->each(function (User $accountUser) use ($page) {
                    $accountUser->pages()->detach($page->id);
                });
        } else {
            $user->pages()->detach($page->id);
        }

        return response()->json([
            'success' => true,
            'message' => $cascaded
                ? 'Página quitada del usuario principal y de la cuenta.'
                : 'Página quitada del usuario.',
            'page_id' => $page->id,
            'cascaded' => $cascaded,
            'user' => $this->userPayload($user->fresh(), $actor),
        ]);
    }

    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        $actor = $request->user();

        if (! $actor) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }

        if ($actor->isAdmin()) {
            return null;
        }

        $role = strtolower(trim((string) $actor->typeUser?->description));

        if ($role === 'cliente') {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no puede administrar los usuarios de la cuenta.',
            ], 403);
        }

        if ($actor->isAccountPrincipal() || $actor->hasPageUrl('usuarios')) {
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

    private function accountUsers(User $actor): Collection
    {
        return User::with(['status', 'typeUser', 'pages.module'])
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

    private function userPayload(User $user, User $actor): array
    {
        $user->loadMissing(['status', 'typeUser', 'pages.module']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->typeUser?->description,
            'status' => $user->status?->status,
            'status_id' => $user->user_status_id,
            'is_principal' => $user->isAccountPrincipal(),
            'is_self' => (int) $user->id === (int) $actor->id,
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
        ];
    }
}

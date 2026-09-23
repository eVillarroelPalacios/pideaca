<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TypeUser;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

class TypeUserController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $typeUsers = TypeUser::withCount('users')->orderBy('description')->get();

        return response()->json($typeUsers);
    }

    public function pagesData()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $typeUsers = TypeUser::withCount('users')->with('assignedPages')->orderBy('description')->get()->map(function ($type) {
            return [
                'id' => $type->id,
                'description' => $type->description,
                'users_count' => $type->users_count,
                'assigned_page_ids' => $type->assignedPages->pluck('id')->unique()->values(),
            ];
        });

        $pages = Page::with('module')->orderBy('description')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'description' => $p->description,
                'url' => $p->url,
                'module_id' => $p->module_id,
                'module' => $p->module ? ($p->module->description ?? $p->module->name) : null,
            ];
        });

        return response()->json([
            'types' => $typeUsers,
            'pages' => $pages,
        ]);
    }

    public function updatePages(Request $request, TypeUser $typeUser)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'page_ids' => 'required|array',
            'page_ids.*' => 'integer|exists:pages,id',
        ]);

        $pageIds = array_values(array_unique(array_map('intval', $request->page_ids)));

        DB::transaction(function () use ($typeUser, $pageIds) {
            $typeUser->assignedPages()->sync($pageIds);

            foreach ($typeUser->users as $user) {
                $user->pages()->sync($pageIds);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Páginas actualizadas para el tipo "' . $typeUser->description . '".',
            'users_count' => $typeUser->users()->count(),
        ]);
    }

    public function attachPage(TypeUser $typeUser, Page $page)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        DB::transaction(function () use ($typeUser, $page) {
            $typeUser->assignedPages()->syncWithoutDetaching([$page->id]);

            foreach ($typeUser->users as $user) {
                $user->pages()->syncWithoutDetaching([$page->id]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Página agregada.',
            'page_id' => $page->id,
        ]);
    }

    public function detachPage(TypeUser $typeUser, Page $page)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        DB::transaction(function () use ($typeUser, $page) {
            $typeUser->assignedPages()->detach($page->id);

            foreach ($typeUser->users as $user) {
                $user->pages()->detach($page->id);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Página quitada.',
            'page_id' => $page->id,
        ]);
    }

    public function bulkPages(Request $request, TypeUser $typeUser)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'page_ids' => 'required|array',
            'page_ids.*' => 'integer|exists:pages,id',
            'action' => 'required|in:attach,detach',
        ]);

        $pageIds = array_values(array_unique(array_map('intval', $request->page_ids)));
        $action = $request->action;

        if (empty($pageIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Sin cambios.',
                'action' => $action,
            ]);
        }

        DB::transaction(function () use ($typeUser, $pageIds, $action) {
            if ($action === 'attach') {
                $typeUser->assignedPages()->syncWithoutDetaching($pageIds);

                foreach ($typeUser->users as $user) {
                    $user->pages()->syncWithoutDetaching($pageIds);
                }
            } else {
                $typeUser->assignedPages()->detach($pageIds);

                foreach ($typeUser->users as $user) {
                    $user->pages()->detach($pageIds);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $action === 'attach' ? 'Páginas agregadas.' : 'Páginas quitadas.',
            'action' => $action,
            'count' => count($pageIds),
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:type_users,description',
        ]);

        $typeUser = TypeUser::create([
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario creado correctamente.',
            'type_user' => $typeUser,
        ]);
    }

    public function update(Request $request, TypeUser $typeUser)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:type_users,description,' . $typeUser->id,
        ]);

        $typeUser->update([
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario actualizado correctamente.',
            'type_user' => $typeUser,
        ]);
    }

    public function destroy(TypeUser $typeUser)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($typeUser->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de usuario porque tiene usuarios asociados.',
            ]);
        }

        $typeUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario eliminado correctamente.',
        ]);
    }
}
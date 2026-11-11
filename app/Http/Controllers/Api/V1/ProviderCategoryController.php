<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Categorias del catalogo del comercio para la app movil.
 *
 * Espeja CategoryController del panel web: mismo nombre limpio, sin
 * duplicados (case-insensitive), reorden por sort_order y borrado bloqueado
 * cuando la categoria todavia tiene productos.
 */
class ProviderCategoryController extends Controller
{
    use ResolvesProvider;

    public function store(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
        ], [
            'name.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
        ]);

        $nombre = $this->limpio($data['name']);

        if (! is_string($nombre) || mb_strlen($nombre) < 2) {
            throw ValidationException::withMessages([
                'name' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
            ]);
        }

        if ($this->duplicada($provider->id, $nombre)) {
            throw ValidationException::withMessages([
                'name' => 'Ya tenés una categoría con ese nombre.',
            ]);
        }

        $category = Category::create([
            'provider_id' => $provider->id,
            'name' => $nombre,
            'sort_order' => (int) (Category::where('provider_id', $provider->id)->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'category' => $this->payload($category),
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $category = Category::where('id', $category->id)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $category) {
            return $this->ajena();
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:80'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
        ]);

        if (array_key_exists('name', $data)) {
            $nombre = $this->limpio($data['name']);

            if (! is_string($nombre) || mb_strlen($nombre) < 2) {
                throw ValidationException::withMessages([
                    'name' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
                ]);
            }

            if ($this->duplicada($category->provider_id, $nombre, $category->id)) {
                throw ValidationException::withMessages([
                    'name' => 'Ya tenés una categoría con ese nombre.',
                ]);
            }

            $data['name'] = $nombre;
        }

        $category->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada.',
            'category' => $this->payload($category->fresh()),
        ]);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $category = Category::where('id', $category->id)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $category) {
            return $this->ajena();
        }

        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede borrar: tiene '
                    .$category->products()->count()
                    .' producto(s). Movelos o borralos primero.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada.',
        ]);
    }

    private function duplicada(int $providerId, string $nombre, ?int $excluyendo = null): bool
    {
        return Category::where('provider_id', $providerId)
            ->when($excluyendo, fn ($q) => $q->where('id', '!=', $excluyendo))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($nombre)])
            ->exists();
    }

    private function limpio(string $valor): ?string
    {
        $espacios = preg_replace('/\s+/u', ' ', $valor);

        return is_string($espacios) ? trim($espacios) : null;
    }

    private function ajena(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'La categoría no pertenece a este comercio.',
        ], 403);
    }

    private function payload(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'is_active' => (bool) $category->is_active,
            'products_count' => $category->products()->count(),
        ];
    }
}

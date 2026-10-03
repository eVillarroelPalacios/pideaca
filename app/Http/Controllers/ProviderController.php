<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Module;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProviderController extends Controller
{
    /**
     * Comercios publicados para el cliente (pagina "Comercios").
     *
     * Un comercio pausado no aparece en el listado, igual que en su catalogo
     * individual. Devuelve ademas los limites del modulo para que el cliente
     * pueda estimar el envio antes de confirmar.
     *
     * Con module_id el listado se recorta a los comercios cuya categoria
     * (rubro) corresponde a ese modulo: dentro de "Comercio & Gastronomia"
     * solo se ven los prestadores de esa categoria.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        $request->validate([
            'module_id' => ['sometimes', 'integer', 'exists:modules,id'],
            'only_with_plans' => ['sometimes', 'boolean'],
            'favorite' => ['sometimes', 'boolean'],
        ]);

        $onlyWithPlans = $request->boolean('only_with_plans');
        $favoriteOnly = $request->boolean('favorite');
        $userId = Auth::id();

        if ($favoriteOnly && ! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Iniciá sesión para ver tus comercios favoritos.',
            ], 401);
        }

        $module = $request->filled('module_id')
            ? Module::find($request->integer('module_id'))
            : null;

        $base = Provider::query()
            ->where('is_active', true)
            ->when($module, function ($query) use ($module) {
                $name = mb_strtolower(trim($module->description));

                $query->whereHas('category', fn ($category) => $category
                    ->whereRaw('LOWER(description) = ?', [$name]));
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereRaw('LOWER(business_name) LIKE ?', ['%'.mb_strtolower($search).'%']);
            });

        $totalCount = (clone $base)->count();
        $favoritesCount = $userId
            ? (clone $base)->whereHas('favorites', fn ($query) => $query->where('user_id', $userId))->count()
            : 0;

        $favoriteIds = $userId
            ? Favorite::where('user_id', $userId)->pluck('provider_id')
            : collect();

        $providers = (clone $base)
            ->when($onlyWithPlans, function ($query) {
                $query->whereHas('subscriptionPlans', fn ($plans) => $plans->where('is_active', true));
            })
            ->when($favoriteOnly, function ($query) use ($favoriteIds) {
                $query->whereIn('id', $favoriteIds->all());
            })
            ->withCount([
                'categories as categories_count' => fn ($query) => $query->reorder()->where('is_active', true),
                'products as products_count' => fn ($query) => $query->reorder()->where('is_available', true),
                'subscriptionPlans as active_subscription_plans_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->with(['category:id,description', 'images' => fn ($query) => $query->where('image_type', 'publicidad')])
            ->orderBy('business_name')
            ->get()
            ->map(fn (Provider $provider) => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'description' => $provider->description,
                'rubro' => $provider->category?->description,
                'zone' => $provider->zone,
                'rating' => $provider->rating,
                'promo' => $provider->promo,
                'phone' => $provider->phone,
                'whatsapp' => $provider->whatsapp,
                'hours' => $provider->hours,
                'banner_image' => $provider->banner_image,
                'categories_count' => (int) $provider->categories_count,
                'products_count' => (int) $provider->products_count,
                'active_subscription_plans_count' => (int) $provider->active_subscription_plans_count,
                'publicidad_image' => $this->publicidadImageUrl($provider),
                'is_favorite' => $favoriteIds->contains($provider->id),
            ]);

        return response()->json([
            'success' => true,
            'settings' => [
                'delivery_fee' => (float) config('fastdelivery.delivery_fee'),
                'max_items_per_order' => (int) config('fastdelivery.max_items_per_order'),
                'max_quantity_per_item' => (int) config('fastdelivery.max_quantity_per_item'),
            ],
            'providers' => $providers,
            'favorites_count' => $favoritesCount,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * Agrega o quita el comercio de los favoritos del usuario logueado.
     * Devuelve el nuevo estado para que la tarjeta se actualice en el acto.
     */
    public function toggleFavorite(Provider $provider): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Iniciá sesión para guardar comercios favoritos.',
            ], 401);
        }

        $existing = Favorite::where('user_id', Auth::id())
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => Auth::id(),
                'provider_id' => $provider->id,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'favorites_count' => Favorite::where('user_id', Auth::id())->count(),
        ]);
    }

    /**
     * Imagen de publicidad del comercio para las tarjetas: sale de las
     * imagenes subidas desde el perfil y, si no quedo registro, del archivo
     * provider_<id>.<ext> que usa el sitio de publicidad.
     */
    private function publicidadImageUrl(Provider $provider): ?string
    {
        $path = $provider->images->first()?->image_path;

        if (! $path) {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidate = 'provider_'.$provider->id.'.'.$ext;

                if (file_exists(public_path('images/publicidad/'.$candidate))) {
                    $path = $candidate;
                    break;
                }
            }
        }

        return $path ? asset('images/publicidad/'.$path) : null;
    }
}

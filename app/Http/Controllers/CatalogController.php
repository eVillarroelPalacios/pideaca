<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogController extends Controller
{
    /**
     * Catalogo completo de un comercio: categorias, productos, variantes y opciones
     */
    public function show(Request $request, Provider $provider)
    {
        // El dueño ve su propio comercio aunque lo tenga pausado: es quien
        // lo administra desde el panel. Para el resto sigue oculto.
        $isOwner = Auth::check() && (int) $provider->user_id === (int) Auth::id();

        if (! $provider->is_active && ! $isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'El comercio no esta disponible.',
            ], 404);
        }

        $onlyAvailable = $request->boolean('only_available');

        $categories = $provider->categories()
            ->where('is_active', true)
            ->with(['products' => function ($query) use ($onlyAvailable) {
                $query->with(['variants' => function ($variants) use ($onlyAvailable) {
                    if ($onlyAvailable) {
                        $variants->where('is_available', true);
                    }
                }, 'optionGroups' => function ($groups) use ($onlyAvailable) {
                    $groups->with(['options' => function ($options) use ($onlyAvailable) {
                        if ($onlyAvailable) {
                            $options->where('is_available', true);
                        }
                    }]);
                }])
                    ->when($onlyAvailable, fn ($q) => $q->where('is_available', true))
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        if ($onlyAvailable) {
            $categories = $categories->filter(
                fn ($category) => $category->products->isNotEmpty()
            )->values();
        }

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'description' => $provider->description,
                'zone' => $provider->zone,
                'rating' => $provider->rating,
                'promo' => $provider->promo,
                'phone' => $provider->phone,
                'whatsapp' => $provider->whatsapp,
                'hours' => $provider->hours,
                'is_active' => (bool) $provider->is_active,
                'categories' => [
                    'id' => $provider->category->id ?? null,
                    'name' => $provider->category->description ?? null,
                ],
                'subgroups' => $provider->subgroups->map(fn ($subgroup) => [
                    'id' => $subgroup->id,
                    'name' => $subgroup->description,
                ]),
            ],
            'categories' => $categories,
        ]);
    }
}

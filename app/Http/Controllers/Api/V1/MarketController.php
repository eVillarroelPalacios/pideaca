<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\CustomerSubscription;
use App\Models\Favorite;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderRating;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\SubscriptionPlan;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mercado para las apps moviles (seccion "Comercios").
 *
 * Espeja las vistas y controladores web de Mercado sin reutilizar el
 * catalogo de Hogar (CatalogController@index): aca solo entran los
 * comercios cuya categoria pertenece al modulo que publica la pagina
 * "comercios". Los precios y el envio se resuelven siempre en el backend.
 */
class MarketController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * Listado de comercios, con busqueda, favoritos y limites del pedido.
     * Mismo esquema que ProviderController@index de la web.
     */
    public function shops(Request $request)
    {
        try {
            $data = $request->validate([
                'module_id' => ['sometimes', 'integer', 'exists:modules,id'],
                'search' => ['nullable', 'string', 'max:100'],
                'favorite' => ['sometimes', 'boolean'],
                'only_with_plans' => ['sometimes', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros de búsqueda inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = $request->user();
        $search = trim((string) ($data['search'] ?? ''));
        $moduleId = ! empty($data['module_id']) ? (int) $data['module_id'] : $this->marketModuleId();

        $base = $this->baseQuery($moduleId)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereRaw('LOWER(business_name) LIKE ?', ['%'.mb_strtolower($search).'%']);
            });

        $totalCount = (clone $base)->count();
        $favoriteIds = Favorite::where('user_id', $user->id)->pluck('provider_id');
        $favoritesCount = (clone $base)->whereIn('id', $favoriteIds)->count();

        $providers = $base
            ->when($request->boolean('only_with_plans'), function ($query) {
                $query->whereHas('subscriptionPlans', fn ($plans) => $plans->where('is_active', true));
            })
            ->when($request->boolean('favorite'), fn ($query) => $query->whereIn('id', $favoriteIds))
            ->withCount([
                'categories as categories_count' => fn ($query) => $query->reorder()->where('is_active', true),
                'products as products_count' => fn ($query) => $query->reorder()->where('is_available', true),
                'subscriptionPlans as active_subscription_plans_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->with(['category:id,description', 'images' => fn ($query) => $query->where('image_type', 'publicidad')])
            ->orderBy('business_name')
            ->get()
            ->map(fn (Provider $provider) => $this->shopPayload($provider, $favoriteIds));

        return response()->json([
            'success' => true,
            'settings' => $this->settings(),
            'providers' => $providers,
            'favorites_count' => $favoritesCount,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * Agrega o quita el comercio de los favoritos del usuario logueado.
     */
    public function toggleFavorite(Request $request, Provider $provider): JsonResponse
    {
        $moduleId = $this->marketModuleId();
        $visible = $this->baseQuery($moduleId)->whereKey($provider->id)->exists();

        if (! $visible) {
            return response()->json([
                'success' => false,
                'message' => 'El comercio no está disponible.',
                'code' => 'SHOP_NOT_FOUND',
            ], 404);
        }

        $user = $request->user();
        $existing = Favorite::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'provider_id' => $provider->id,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'favorites_count' => Favorite::where('user_id', $user->id)->count(),
        ]);
    }

    /**
     * Catalogo completo del comercio: categorias, productos, variantes y
     * opciones, con la direccion de retiro y el costo de envio.
     */
    public function catalog(Request $request, Provider $provider)
    {
        $moduleId = $this->marketModuleId();

        if (! $this->baseQuery($moduleId)->whereKey($provider->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El comercio no está disponible.',
                'code' => 'SHOP_NOT_FOUND',
            ], 404);
        }

        $onlyAvailable = ! $request->has('only_available') || $request->boolean('only_available');

        $categories = $provider->categories()
            ->where('is_active', true)
            ->with(['products' => function ($query) use ($onlyAvailable) {
                $query->with([
                    'inventory',
                    'variants' => function ($variants) use ($onlyAvailable) {
                        if ($onlyAvailable) {
                            $variants->where('is_available', true);
                        }
                    },
                    'optionGroups' => function ($groups) use ($onlyAvailable) {
                        $groups->with(['options' => function ($options) use ($onlyAvailable) {
                            if ($onlyAvailable) {
                                $options->where('is_available', true);
                            }
                        }]);
                    },
                ])
                    ->when($onlyAvailable, fn ($q) => $q->where('is_available', true))
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        if ($onlyAvailable) {
            $categories = $categories
                ->filter(fn ($category) => $category->products->isNotEmpty())
                ->values();
        }

        $usesInventory = $provider->usesInventory();
        $provider->loadMissing('user.primaryAddress', 'category');

        $payloadCategories = $categories->map(function ($category) use ($usesInventory) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'products' => $category->products->map(function (Product $product) use ($usesInventory) {
                    $inventory = $product->inventory;
                    $outOfStock = $usesInventory
                        && $product->track_stock
                        && ($inventory ? (float) $inventory->current_stock <= 0 : true)
                        && ! ($inventory && $inventory->allow_negative_stock);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => (float) $product->price,
                        'image_url' => $product->image_path ? asset('images/'.$product->image_path) : null,
                        'is_out_of_stock' => $outOfStock,
                        'variants' => $product->variants
                            ->map(fn (ProductVariant $variant) => [
                                'id' => $variant->id,
                                'name' => $variant->name,
                                'price' => (float) $variant->price,
                            ])
                            ->values()
                            ->all(),
                        'option_groups' => $product->optionGroups
                            ->map(fn ($group) => [
                                'id' => $group->id,
                                'name' => $group->name,
                                'min_choices' => (int) $group->min_choices,
                                'max_choices' => (int) $group->max_choices,
                                'is_required' => (bool) $group->is_required,
                                'options' => $group->options
                                    ->map(fn (ProductOption $option) => [
                                        'id' => $option->id,
                                        'name' => $option->name,
                                        'extra_price' => (float) $option->extra_price,
                                    ])
                                    ->values()
                                    ->all(),
                            ])
                            ->values()
                            ->all(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'settings' => $this->settings(),
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'description' => $provider->description,
                'zone' => $provider->zone,
                'rating' => $provider->rating !== null ? (float) $provider->rating : null,
                'promo' => $provider->promo,
                'phone' => $provider->phone,
                'whatsapp' => $provider->whatsapp,
                'hours' => $provider->hours,
                'address' => $provider->user?->primaryAddress?->formatted,
                'delivery_fee' => (float) config('fastdelivery.delivery_fee'),
                'category' => $provider->category ? [
                    'id' => $provider->category->id,
                    'name' => $provider->category->description,
                ] : null,
            ],
            'categories' => $payloadCategories,
        ]);
    }

    /**
     * Direcciones del cliente para elegir la entrega del pedido.
     */
    public function addresses(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->with(['country:id,name', 'province:id,name', 'department:id,name', 'region:id,name'])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'street' => $address->street,
                'number' => $address->number,
                'floor_apartment' => $address->floor_apartment,
                'postal_code' => $address->postal_code,
                'notes' => $address->notes,
                'is_primary' => (bool) $address->is_primary,
                'formatted' => $address->formatted,
                'latitude' => $address->latitude !== null ? (float) $address->latitude : null,
                'longitude' => $address->longitude !== null ? (float) $address->longitude : null,
            ]);

        return response()->json([
            'success' => true,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Registra el carrito como pedido de Mercado. Los precios NUNCA se toman
     * del cliente: se resuelven desde la base dentro de la transaccion.
     */
    public function storeOrder(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'delivery_type' => ['nullable', 'string', 'in:'.Order::DELIVERY_TYPE_DELIVERY.','.Order::DELIVERY_TYPE_TAKEAWAY],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:'.config('fastdelivery.max_items_per_order')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('fastdelivery.max_quantity_per_item')],
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*' => ['integer'],
        ]);

        $deliveryType = $data['delivery_type'] ?? Order::DELIVERY_TYPE_DELIVERY;

        $provider = $this->baseQuery($this->marketModuleId())
            ->whereKey($data['provider_id'])
            ->first();

        if (! $provider) {
            throw ValidationException::withMessages([
                'provider_id' => 'El comercio no está disponible.',
            ]);
        }

        $address = null;

        if ($deliveryType === Order::DELIVERY_TYPE_DELIVERY) {
            if (empty($data['address_id'])) {
                throw ValidationException::withMessages([
                    'address_id' => 'Seleccioná la dirección de entrega.',
                ]);
            }

            $address = Address::where('id', $data['address_id'])
                ->where('user_id', $user->id)
                ->first();

            if (! $address) {
                throw ValidationException::withMessages([
                    'address_id' => 'La dirección de entrega no pertenece a tu usuario.',
                ]);
            }
        }

        $order = DB::transaction(function () use ($data, $deliveryType, $provider, $user, $address) {
            $order = Order::create([
                'provider_id' => $provider->id,
                'user_id' => $user->id,
                'address_id' => $address?->id,
                'order_number' => Order::nextNumber(),
                'status' => Order::STATUS_PENDING,
                'delivery_type' => $deliveryType,
                'delivery_status' => $deliveryType === Order::DELIVERY_TYPE_TAKEAWAY
                    ? Order::DELIVERY_STATUS_NOT_REQUIRED
                    : 'pending',
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0.0;
            $stockLines = [];

            foreach ($data['items'] as $index => $line) {
                $resolved = $this->resolveLine($provider, $line, $index);
                $unitPrice = $resolved['unit_price'];
                $quantity = (int) $line['quantity'];

                $stockLines[] = [
                    'product' => $resolved['product'],
                    'quantity' => $quantity,
                    'key' => "items.$index.quantity",
                ];

                $optionsTotal = $resolved['options']->sum(fn (ProductOption $option) => (float) $option->extra_price);
                $lineTotal = ($unitPrice + $optionsTotal) * $quantity;

                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $resolved['product']->id,
                    'variant_id' => $resolved['variant']?->id,
                    'product_name' => $resolved['product']->name,
                    'variant_name' => $resolved['variant']?->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);

                foreach ($resolved['options'] as $option) {
                    OrderItemOption::create([
                        'order_item_id' => $item->id,
                        'group_name' => $option->optionGroup->name,
                        'option_name' => $option->name,
                        'extra_price' => $option->extra_price,
                    ]);
                }

                $subtotal += $lineTotal;
            }

            $this->inventory->applySale($provider, $order, $stockLines);

            $deliveryFee = $deliveryType === Order::DELIVERY_TYPE_TAKEAWAY
                ? 0.0
                : (float) config('fastdelivery.delivery_fee');

            $order->update([
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => 0,
                'total' => $subtotal + $deliveryFee,
            ]);

            return $order;
        });

        $order->load(['items.options', 'address', 'provider:id,business_name']);

        return response()->json([
            'success' => true,
            'message' => 'Pedido registrado correctamente.',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'delivery_type' => $order->delivery_type,
                'subtotal' => (float) $order->subtotal,
                'delivery_fee' => (float) $order->delivery_fee,
                'total' => (float) $order->total,
                'payment_method' => $order->payment_method,
                'notes' => $order->notes,
                'provider' => $order->provider ? [
                    'id' => $order->provider->id,
                    'business_name' => $order->provider->business_name,
                ] : null,
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'options' => $item->options->map(fn (OrderItemOption $option) => [
                        'group_name' => $option->group_name,
                        'option_name' => $option->option_name,
                        'extra_price' => (float) $option->extra_price,
                    ])->values()->all(),
                ])->values()->all(),
            ],
        ], 201);
    }

    /**
     * Pedidos del cliente logueado: alimenta "Mis Pedidos" en la app.
     *
     * Espeja OrderController@mine de la web: solo los propios, con el
     * comercio, el reparto, la direccion, el pago y las calificaciones.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status');

        if ($status !== null && (
            ! is_string($status)
            || ($status !== '' && ! in_array($status, Order::STATUSES, true))
        )) {
            return response()->json([
                'success' => false,
                'message' => 'El estado indicado no es válido.',
                'errors' => ['status' => ['El estado indicado no es válido.']],
            ], 422);
        }

        try {
            $orders = $user->orders()
                ->with([
                    'provider:id,business_name,is_active,user_id',
                    // Telefono del comercio para el boton "Ayuda / Contacto".
                    'provider.user:id,name,phone',
                    // Direccion del local para los pedidos con retiro.
                    'provider.user.primaryAddress',
                    // El cliente ve quien lleva su pedido en el circuito.
                    'driver:id,name,phone',
                    'items.options',
                    'address:id,street,number,floor_apartment,postal_code,notes',
                    'payments:id,order_id,provider,status,amount,created_at',
                    'ratings',
                ])
                ->when($status, fn ($query) => $query->withStatus($status))
                ->take(100)
                ->get()
                ->map(function (Order $order) {
                    $customerRating = $order->ratings->first(
                        fn ($rating) => (int) $rating->reviewer_id === (int) $order->user_id
                    );
                    $providerRating = $order->ratings->first(
                        fn ($rating) => (int) $rating->reviewer_id === (int) $order->provider?->user_id
                    );

                    $payload = $order->toArray();
                    $payload['pickup_address'] = $order->provider?->user?->primaryAddress?->formatted;
                    $payload['customer_rated'] = (bool) $customerRating;
                    $payload['customer_rating'] = $customerRating ? [
                        'stars' => $customerRating->stars,
                        'comment' => $customerRating->comment,
                    ] : null;
                    $payload['provider_rated'] = (bool) $providerRating;
                    $payload['provider_rating'] = $providerRating ? [
                        'stars' => $providerRating->stars,
                        'comment' => $providerRating->comment,
                    ] : null;

                    return $payload;
                });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los pedidos.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'statuses' => Order::STATUSES,
            'orders' => $orders,
        ]);
    }

    /**
     * Califica un pedido entregado. Misma validacion que la web: una sola
     * calificacion por autor y pedido, y el comercio actualiza su promedio.
     */
    public function rateOrder(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $order->loadMissing(['provider', 'ratings']);

        $isCustomer = (int) $order->user_id === (int) $user->id;
        $isMerchant = (int) $order->provider?->user_id === (int) $user->id;

        if (! $isCustomer && ! $isMerchant) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés permisos para calificar este pedido.',
            ], 403);
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden calificar pedidos entregados.',
            ], 422);
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = $order->ratings->first(
            fn ($rating) => (int) $rating->reviewer_id === (int) $user->id
        );

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Ya calificaste este pedido.',
            ], 422);
        }

        $rateeId = $isCustomer ? $order->provider->user_id : $order->user_id;

        try {
            $rating = DB::transaction(function () use ($order, $user, $rateeId, $data) {
                $rating = OrderRating::create([
                    'order_id' => $order->id,
                    'reviewer_id' => $user->id,
                    'ratee_id' => $rateeId,
                    'stars' => $data['stars'],
                    'comment' => $data['comment'] ?? null,
                ]);

                // Promedio consolidado del comercio (igual que la web).
                $providerUserId = $order->provider->user_id;
                $allRatings = OrderRating::where('ratee_id', $providerUserId);

                $order->provider->rating = round((float) $allRatings->avg('stars'), 2);
                $order->provider->rating_count = (int) $allRatings->count();
                $order->provider->save();

                return $rating;
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la calificación. Intentá nuevamente.',
            ], 500);
        }

        $order->load('ratings');
        $customerRating = $order->ratings->first(
            fn ($rating) => (int) $rating->reviewer_id === (int) $order->user_id
        );
        $providerRating = $order->ratings->first(
            fn ($rating) => (int) $rating->reviewer_id === (int) $order->provider?->user_id
        );

        return response()->json([
            'success' => true,
            'message' => $isCustomer ? 'Gracias por calificar al comercio.' : 'Gracias por calificar al cliente.',
            'rating' => [
                'id' => $rating->id,
                'order_id' => $rating->order_id,
                'reviewer_id' => $rating->reviewer_id,
                'ratee_id' => $rating->ratee_id,
                'stars' => $rating->stars,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at?->toIso8601String(),
            ],
            'customer_rated' => (bool) $customerRating,
            'customer_rating' => $customerRating ? [
                'stars' => $customerRating->stars,
                'comment' => $customerRating->comment,
            ] : null,
            'provider_rated' => (bool) $providerRating,
            'provider_rating' => $providerRating ? [
                'stars' => $providerRating->stars,
                'comment' => $providerRating->comment,
            ] : null,
            'provider_global_rating' => $order->provider->fresh()->rating,
        ]);
    }

    /**
     * Planes activos de un comercio: alimentan el alta de suscripciones.
     */
    public function plans(Provider $provider): JsonResponse
    {
        $visible = $this->baseQuery($this->marketModuleId())->whereKey($provider->id)->exists();

        if (! $visible) {
            return response()->json([
                'success' => false,
                'message' => 'El comercio no está disponible.',
                'code' => 'SHOP_NOT_FOUND',
            ], 404);
        }

        $plans = $provider->subscriptionPlans()
            ->active()
            ->with(['items.product:id,name,price,image_path,is_available'])
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
            ],
            'plans' => $plans->map(fn (SubscriptionPlan $plan) => $this->planPayload($plan))->values(),
        ]);
    }

    /**
     * Suscripciones del cliente logueado.
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = CustomerSubscription::where('user_id', $request->user()->id)
            ->where('status', '!=', CustomerSubscription::STATUS_CANCELLED)
            ->with([
                'plan.items.product:id,name,price,image_path',
                'items.product:id,name,price,image_path',
                'provider:id,business_name',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions
                ->map(fn (CustomerSubscription $subscription) => $this->subscriptionPayload($subscription))
                ->values(),
        ]);
    }

    /**
     * Alta de suscripcion del cliente logueado (misma validacion que la web).
     */
    public function storeSubscription(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'delivery_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'string', 'max:50'],
            'next_delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_delivery_day' => ['nullable', 'integer', 'min:1', 'max:7'],
            'preferred_delivery_time' => ['nullable', 'date_format:H:i'],
            'items' => ['nullable', 'array', 'max:'.config('fastdelivery.max_items_per_order')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('fastdelivery.max_quantity_per_item')],
        ]);

        $plan = SubscriptionPlan::with('provider')
            ->where('id', $data['subscription_plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan || ! $plan->provider || ! $plan->provider->is_active) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'El plan no está disponible.',
            ]);
        }

        $address = Address::where('id', $data['delivery_address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $address) {
            throw ValidationException::withMessages([
                'delivery_address_id' => 'La dirección de entrega no pertenece a tu usuario.',
            ]);
        }

        $alreadySubscribed = CustomerSubscription::where('user_id', $user->id)
            ->where('subscription_plan_id', $plan->id)
            ->whereIn('status', [CustomerSubscription::STATUS_ACTIVE, CustomerSubscription::STATUS_PAUSED])
            ->exists();

        if ($alreadySubscribed) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Ya tenés una suscripción a este plan.',
            ]);
        }

        if (! $plan->hasCapacity()) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Este plan completo no tiene cupos disponibles por el momento.',
            ]);
        }

        $subscription = DB::transaction(function () use ($data, $user, $plan, $address) {
            $locked = SubscriptionPlan::whereKey($plan->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->hasCapacity()) {
                throw ValidationException::withMessages([
                    'subscription_plan_id' => 'Este plan completo no tiene cupos disponibles por el momento.',
                ]);
            }

            $subscription = CustomerSubscription::create([
                'user_id' => $user->id,
                'provider_id' => $plan->provider_id,
                'subscription_plan_id' => $plan->id,
                'status' => CustomerSubscription::STATUS_ACTIVE,
                'next_delivery_date' => $data['next_delivery_date'],
                'preferred_delivery_day' => $data['preferred_delivery_day'] ?? null,
                'preferred_delivery_time' => $data['preferred_delivery_time'] ?? null,
                'delivery_address_id' => $address->id,
                'payment_method' => $data['payment_method'],
            ]);

            if (! empty($data['items'])) {
                $this->replaceItems($subscription, $data['items']);
            }

            return $subscription;
        });

        return response()->json([
            'success' => true,
            'message' => 'Suscripción creada correctamente.',
            'subscription' => $this->subscriptionPayload($subscription->load('items.product')),
        ], 201);
    }

    /**
     * Pausar, reanudar o cancelar una suscripcion propia.
     */
    public function updateSubscriptionStatus(Request $request, CustomerSubscription $subscription)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', CustomerSubscription::CLIENT_STATUSES)],
        ]);

        if ((int) $subscription->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés acceso a esta suscripción.',
            ], 403);
        }

        if ($subscription->status === CustomerSubscription::STATUS_PAYMENT_FAILED
            && $data['status'] === CustomerSubscription::STATUS_ACTIVE
            && $subscription->hasUnpaidCycles()) {
            return response()->json([
                'success' => false,
                'message' => 'Todavía tenés ciclos sin pagar.',
                'errors' => ['status' => ['Regularizá el cobro pendiente antes de reactivar la suscripción.']],
            ], 422);
        }

        $allowed = CustomerSubscription::CLIENT_TRANSITIONS[$subscription->status] ?? [];

        if (! in_array($data['status'], $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'La suscripción no puede pasar de "'.$subscription->status.'" a "'.$data['status'].'".',
                'errors' => ['status' => ['El estado indicado no es válido para esta suscripción.']],
            ], 422);
        }

        $updates = ['status' => $data['status']];

        if ($data['status'] === CustomerSubscription::STATUS_ACTIVE
            && $subscription->next_delivery_date->isBefore(today())) {
            $updates['next_delivery_date'] = today();
        }

        $subscription->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado.',
            'subscription' => $this->subscriptionPayload($subscription->fresh()),
        ]);
    }

    /**
     * Comercios visibles en Mercado: activos y de la categoria del modulo.
     */
    private function baseQuery(?int $moduleId)
    {
        return Provider::query()
            ->where('is_active', true)
            ->when($moduleId, fn ($query) => $query->whereHas(
                'category',
                fn ($category) => $category->where('module_id', $moduleId)
            ));
    }

    /**
     * Modulo que publica la pagina "comercios" (Mercado). Se resuelve por la
     * pagina y no por el nombre del modulo para que renombrarlo no lo rompa.
     */
    private function marketModuleId(): ?int
    {
        return Module::query()
            ->whereHas('pages', fn ($query) => $query->where('url', 'comercios'))
            ->orderBy('id')
            ->value('id');
    }

    private function settings(): array
    {
        return [
            'delivery_fee' => (float) config('fastdelivery.delivery_fee'),
            'max_items_per_order' => (int) config('fastdelivery.max_items_per_order'),
            'max_quantity_per_item' => (int) config('fastdelivery.max_quantity_per_item'),
        ];
    }

    private function shopPayload(Provider $provider, Collection $favoriteIds): array
    {
        return [
            'id' => $provider->id,
            'business_name' => $provider->business_name,
            'description' => $provider->description,
            'rubro' => $provider->category?->description,
            'zone' => $provider->zone,
            'rating' => $provider->rating !== null ? (float) $provider->rating : null,
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
        ];
    }

    /**
     * Imagen de publicidad del comercio (mismo criterio que la web).
     */
    private function publicidadImageUrl(Provider $provider): ?string
    {
        $provider->loadMissing('images');

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

    private function planPayload(SubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'description' => $plan->description,
            'frequency' => $plan->frequency,
            'frequency_label' => [
                SubscriptionPlan::FREQUENCY_DAILY => 'Diario',
                SubscriptionPlan::FREQUENCY_WEEKLY => 'Semanal',
                SubscriptionPlan::FREQUENCY_BIWEEKLY => 'Quincenal',
                SubscriptionPlan::FREQUENCY_MONTHLY => 'Mensual',
            ][$plan->frequency] ?? $plan->frequency,
            'price' => (float) $plan->price,
            'discount_percentage' => (float) $plan->discount_percentage,
            'discount_amount' => $plan->discountAmount(),
            'charge_amount' => $plan->chargeAmount(),
            'capacity' => $plan->capacity,
            'available_slots' => $plan->availableSlots(),
            'is_active' => (bool) $plan->is_active,
            'items' => $plan->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'image_url' => $item->product?->image_path
                    ? asset('images/'.$item->product->image_path)
                    : null,
                'quantity' => (int) $item->quantity,
                'unit_price' => $item->product ? (float) $item->product->price : null,
                'line_total' => $item->product ? $plan->lineTotal($item) : null,
            ])->values()->all(),
        ];
    }

    private function subscriptionPayload(CustomerSubscription $subscription): array
    {
        $subscription->loadMissing(['plan', 'provider:id,business_name', 'items.product:id,name,price,image_path']);

        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'next_delivery_date' => $subscription->next_delivery_date?->toDateString(),
            'preferred_delivery_day' => $subscription->preferred_delivery_day,
            'preferred_delivery_time' => $subscription->preferred_delivery_time?->format('H:i'),
            'payment_method' => $subscription->payment_method,
            'delivery_address_id' => $subscription->delivery_address_id,
            'provider' => $subscription->provider ? [
                'id' => $subscription->provider->id,
                'business_name' => $subscription->provider->business_name,
            ] : null,
            'plan' => $subscription->plan ? $this->planPayload($subscription->plan) : null,
            'items' => $subscription->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'image_url' => $item->product?->image_path
                    ? asset('images/'.$item->product->image_path)
                    : null,
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
        ];
    }

    /**
     * Deja los items de la suscripcion listos para el proximo pedido.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function replaceItems(CustomerSubscription $subscription, array $items): void
    {
        $ids = array_column($items, 'product_id');

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'items' => 'No repitas el mismo producto.',
            ]);
        }

        $plan = $subscription->plan;
        $permitidos = $plan->items->pluck('product_id')->all();
        $fueraDePlan = array_values(array_diff($ids, $permitidos));

        if ($fueraDePlan !== []) {
            throw ValidationException::withMessages([
                'items' => array_map(
                    fn ($id) => 'El producto '.$id.' no forma parte de este plan.',
                    $fueraDePlan
                ),
            ]);
        }

        $totalPlan = (int) $plan->items->sum('quantity');
        $totalPedido = array_sum(array_map(fn ($item) => (int) $item['quantity'], $items));

        if ($totalPedido !== $totalPlan) {
            throw ValidationException::withMessages([
                'items' => 'La cantidad total debe sumar '.$totalPlan.', que es lo que incluye el plan.',
            ]);
        }

        $subscription->items()->delete();

        foreach ($items as $item) {
            $subscription->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    /**
     * Resuelve una linea del carrito contra la base (producto, variante,
     * precio unitario y opciones validas).
     *
     * @return array{product: Product, variant: ProductVariant|null, unit_price: float, options: Collection}
     */
    private function resolveLine(Provider $provider, array $line, int $index): array
    {
        $product = Product::where('id', $line['product_id'])
            ->where('provider_id', $provider->id)
            ->where('is_available', true)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                "items.$index.product_id" => 'El producto no existe o no está disponible en este comercio.',
            ]);
        }

        $variant = null;

        if (! empty($line['variant_id'])) {
            $variant = ProductVariant::where('id', $line['variant_id'])
                ->where('product_id', $product->id)
                ->where('is_available', true)
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    "items.$index.variant_id" => 'La variante seleccionada no está disponible.',
                ]);
            }
        }

        return [
            'product' => $product,
            'variant' => $variant,
            'unit_price' => $variant ? (float) $variant->price : (float) $product->price,
            'options' => $this->resolveOptions($product, $line, $index),
        ];
    }

    /**
     * Valida que las opciones pertenezcan al producto y devuelve el modelo real.
     *
     * @return Collection<int, ProductOption>
     */
    private function resolveOptions(Product $product, array $line, int $index)
    {
        $optionIds = $line['options'] ?? [];

        if (empty($optionIds)) {
            return collect();
        }

        $options = ProductOption::with('optionGroup')
            ->whereIn('id', $optionIds)
            ->where('is_available', true)
            ->whereHas('optionGroup', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->get();

        if ($options->count() !== count(array_unique($optionIds))) {
            throw ValidationException::withMessages([
                "items.$index.options" => 'Una de las opciones no pertenece al producto o no está disponible.',
            ]);
        }

        foreach ($options->groupBy('option_group_id') as $group) {
            $definition = $group->first()->optionGroup;
            $count = $group->count();

            if ($count < $definition->min_choices || $count > $definition->max_choices) {
                throw ValidationException::withMessages([
                    "items.$index.options" => 'La cantidad de opciones de "'.$definition->name.'" no es válida.',
                ]);
            }
        }

        return $options->values();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Pedidos recibidos por un comercio: alimenta la vista "Pedidos" del prestador.
     *
     * Solo el dueño del comercio puede verlos, y nunca se filtran pedidos
     * de otro comercio aunque se pida otro id.
     */
    public function index(Request $request, Provider $provider)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ((int) $provider->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés acceso a este comercio.',
            ], 403);
        }

        $status = $request->query('status');

        if (is_string($status) && $status !== '' && ! in_array($status, Order::STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'El estado indicado no es válido.',
                'errors' => ['status' => ['El estado indicado no es válido.']],
            ], 422);
        }

        $orders = $provider->orders()
            ->with([
                'user:id,name,email',
                'items.options',
                'address:id,street,number,floor_apartment,postal_code,notes',
                'payments:id,order_id,provider,status,amount,created_at',
            ])
            ->when($status, fn ($query) => $query->withStatus($status))
            ->take(100)
            ->get();

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'is_active' => (bool) $provider->is_active,
            ],
            'statuses' => Order::STATUSES,
            'orders' => $orders,
        ]);
    }

    /**
     * Pedidos del cliente logueado: alimenta la vista "Mis Pedidos".
     *
     * Solo los propios, nunca los de otro usuario aunque se pida otro filtro.
     */
    public function mine(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $status = $request->query('status');

        if (is_string($status) && $status !== '' && ! in_array($status, Order::STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'El estado indicado no es válido.',
                'errors' => ['status' => ['El estado indicado no es válido.']],
            ], 422);
        }

        $orders = Auth::user()->orders()
            ->with([
                'provider:id,business_name,is_active',
                'items.options',
                'address:id,street,number,floor_apartment,postal_code,notes',
                'payments:id,order_id,provider,status,amount,created_at',
            ])
            ->when($status, fn ($query) => $query->withStatus($status))
            ->take(100)
            ->get();

        return response()->json([
            'success' => true,
            'statuses' => Order::STATUSES,
            'orders' => $orders,
        ]);
    }

    /**
     * Cambia el estado de un pedido dentro del flujo de seguimiento.
     *
     * Solo el dueno del comercio recorre la cadena (confirmado, en preparacion,
     * en camino, entregado) o cancela; el cliente unicamente puede cancelar su
     * propio pedido mientras este pueda cancelarse.
     */
    public function updateStatus(Request $request, Order $order)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', Order::STATUSES)],
        ]);

        $userId = (int) Auth::id();
        $isProvider = $order->provider !== null && (int) $order->provider->user_id === $userId;
        $isClient = (int) $order->user_id === $userId;

        if (! $isProvider && ! $isClient) {
            return response()->json([
                'success' => false,
                'message' => 'No tenes acceso a este pedido.',
            ], 403);
        }

        $allowed = $order->allowedTransitions(! $isProvider);

        if (! in_array($data['status'], $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'El pedido no puede pasar de "'.$order->status.'" a "'.$data['status'].'".',
                'errors' => ['status' => ['El estado indicado no es valido para este pedido.']],
            ], 422);
        }

        DB::transaction(function () use ($order, $data) {
            if ($data['status'] === Order::STATUS_CANCELLED) {
                $this->restoreInventoryForCancelledOrder($order);
            }

            $order->update(['status' => $data['status']]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado.',
            'status' => $order->status,
            'order' => $order->load([
                'provider:id,business_name,is_active',
                'user:id,name,email',
                'items.options',
                'address:id,street,number,floor_apartment,postal_code,notes',
                'payments:id,order_id,provider,status,amount,created_at',
            ]),
        ]);
    }

    /**
     * Registra el carrito como un pedido.
     *
     * Los precios NUNCA se toman del cliente: se resuelven desde products,
     * product_variants y product_options dentro de la misma transaccion.
     */
    public function store(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        $data = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:'.config('fastdelivery.max_items_per_order')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('fastdelivery.max_quantity_per_item')],
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*' => ['integer'],
        ]);

        $provider = Provider::where('id', $data['provider_id'])->where('is_active', true)->first();

        if (! $provider) {
            throw ValidationException::withMessages([
                'provider_id' => 'El comercio no esta disponible.',
            ]);
        }

        $address = Address::where('id', $data['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $address) {
            throw ValidationException::withMessages([
                'address_id' => 'La direccion de entrega no pertenece a tu usuario.',
            ]);
        }

        $order = DB::transaction(function () use ($data, $provider, $user, $address) {
            $order = Order::create([
                'provider_id' => $provider->id,
                'user_id' => $user->id,
                'address_id' => $address->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => Order::STATUS_PENDING,
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

            $this->applySaleToInventory($provider, $order, $stockLines);

            $deliveryFee = (float) config('fastdelivery.delivery_fee');

            $order->update([
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                // El descuento aplica sobre lo que el backend calcula (cupones en un paso posterior).
                'discount' => 0,
                'total' => $subtotal + $deliveryFee,
            ]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pedido registrado correctamente.',
            'order' => $order->load('items.options'),
        ], 201);
    }

    private function applySaleToInventory(Provider $provider, Order $order, array $lines): void
    {
        if (! $provider->usesInventory()) {
            return;
        }

        foreach ($lines as $line) {
            $product = $line['product'];

            if (! $product->track_stock) {
                continue;
            }

            $unit = $this->resolveInventoryUnit($product);
            $factor = (float) $unit->base_conversion_factor;
            $base = round(((int) $line['quantity']) * $factor, 3);

            $inventory = ProductInventory::where('product_id', $product->id)
                ->where('provider_id', $provider->id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = ProductInventory::create([
                    'product_id' => $product->id,
                    'provider_id' => $provider->id,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'allow_negative_stock' => false,
                ]);
            }

            $newStock = round(((float) $inventory->current_stock) - $base, 3);

            if ($newStock < 0 && ! $inventory->allow_negative_stock) {
                throw ValidationException::withMessages([
                    $line['key'] => 'Stock insuficiente de "'.$product->name.'": hay '.$inventory->current_stock.' y se requieren '.$base.'.',
                ]);
            }

            $inventory->update(['current_stock' => $newStock]);

            InventoryMovement::create([
                'product_id' => $product->id,
                'provider_id' => $provider->id,
                'order_id' => $order->id,
                'movement_type' => 'SALE',
                'quantity' => (int) $line['quantity'],
                'unit_of_measure_id' => $unit->id,
                'unit_conversion_factor' => round($factor, 4),
                'quantity_in_base_unit' => $base,
                'notes' => null,
            ]);
        }
    }

    private function restoreInventoryForCancelledOrder(Order $order): void
    {
        $sales = InventoryMovement::where('order_id', $order->id)
            ->where('movement_type', 'SALE')
            ->lockForUpdate()
            ->get();

        if ($sales->isEmpty()) {
            return;
        }

        foreach ($sales as $sale) {
            $restore = round((float) $sale->quantity_in_base_unit, 3);

            $inventory = ProductInventory::where('product_id', $sale->product_id)
                ->where('provider_id', $sale->provider_id)
                ->lockForUpdate()
                ->first();

            if ($inventory) {
                $inventory->update([
                    'current_stock' => round(((float) $inventory->current_stock) + $restore, 3),
                ]);
            } else {
                ProductInventory::create([
                    'product_id' => $sale->product_id,
                    'provider_id' => $sale->provider_id,
                    'current_stock' => $restore,
                    'reserved_stock' => 0,
                    'allow_negative_stock' => false,
                ]);
            }

            InventoryMovement::create([
                'product_id' => $sale->product_id,
                'provider_id' => $sale->provider_id,
                'order_id' => $order->id,
                'movement_type' => 'CANCELLED_SALE',
                'quantity' => $sale->quantity,
                'unit_of_measure_id' => $sale->unit_of_measure_id,
                'unit_conversion_factor' => $sale->unit_conversion_factor,
                'quantity_in_base_unit' => $sale->quantity_in_base_unit,
                'notes' => 'Cancelación del pedido '.$order->order_number,
            ]);
        }
    }

    private function resolveInventoryUnit(Product $product): UnitOfMeasure
    {
        $unit = $product->unitOfMeasure
            ?? UnitOfMeasure::where('name', 'Unidad')->first()
            ?? UnitOfMeasure::first();

        if (! $unit) {
            throw ValidationException::withMessages([
                'items' => 'No hay unidades de medida configuradas para descontar inventario.',
            ]);
        }

        return $unit;
    }

    /**
     * Resuelve una linea del carrito contra la base: producto, variante, precio
     * unitario y opciones validas. Ningun valor proviene del cliente.
     *
     * @return array{product: Product, variant: ProductVariant|null, unit_price: float, options: \Illuminate\Support\Collection}
     */
    private function resolveLine(Provider $provider, array $line, int $index): array
    {
        $key = "items.$index.product_id";
        $variantKey = "items.$index.variant_id";

        $product = Product::where('id', $line['product_id'])
            ->where('provider_id', $provider->id)
            ->where('is_available', true)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                $key => 'El producto no existe o no esta disponible en este comercio.',
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
                    $variantKey => 'La variante seleccionada no esta disponible.',
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
     * @return \Illuminate\Support\Collection<int, ProductOption>
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
                "items.$index.options" => 'Una de las opciones no pertenece al producto o no esta disponible.',
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

    private function generateOrderNumber(): string
    {
        $prefix = config('fastdelivery.order_number_prefix');

        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }
}

<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\Group;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\Region;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FastDeliverySeeder extends Seeder
{
    private const PROVIDER_EMAIL = 'donpepe@pideaca.com';

    private const CUSTOMER_EMAIL = 'cliente.demo@pideaca.com';

    private const LOS_HERMANOS_EMAIL = 'losh@loshermanos.com.ar';

    private const ORDER_NUMBER = 'PIDE-2026-0001';

    private const DELIVERY_FEE = 3500.00;

    private const DISCOUNT = 2000.00;

    public function run(): void
    {
        DB::transaction(function () {
            $this->assignProviderRubros();
            $provider = $this->seedProvider();
            [$customer, $address] = $this->seedCustomer();
            $catalog = $this->seedCatalog($provider);
            $this->seedOrder($provider, $customer, $address, $catalog);

            $this->seedLosHermanosDemo();
        });

        $this->command?->newLine();
        $this->command?->info('Fast Delivery: comercio, catálogo y pedido de prueba listos.');
    }

    /**
     * Comercio: Pizzería Don Pepe
     */
    private function seedProvider(): Provider
    {
        $prestador = TypeUser::firstOrCreate(['description' => 'Prestador']);
        $activo = UserStatus::firstOrCreate(['status' => 'Activo']);

        $group = Group::firstOrCreate(['description' => 'Comercio & Gastronomía']);
        $subgroup = SubGroup::firstOrCreate([
            'description' => 'Pizzerías',
            'group_id' => $group->id,
        ]);

        $user = User::updateOrCreate(
            ['email' => self::PROVIDER_EMAIL],
            [
                'name' => 'Pizzería Don Pepe',
                'password' => 'password',
                'user_status_id' => $activo->id,
                'type_user_id' => $prestador->id,
                'email_verified_at' => now(),
            ]
        );

        $provider = Provider::updateOrCreate(
            ['user_id' => $user->id],
            [
                'category_id' => $group->id,
                'business_name' => 'Pizzería Don Pepe',
                'description' => 'Pizzas artesanales de masa madre, empanadas caseras y delivery en 30 minutos.',
                'rating' => 4.80,
                'promo' => '2x1 en pizzas tradicionales los martes',
                'phone' => '+54 11 5555-1234',
                'whatsapp' => '+54 9 11 5555-1234',
                'zone' => 'Palermo, Recoleta y Almagro',
                'hours' => $this->businessHours(),
                'is_active' => true,
            ]
        );

        $provider->subgroups()->syncWithoutDetaching([$subgroup->id]);

        return $provider->fresh();
    }

    /**
     * Categoria (rubro) de los comercios de demo.
     *
     * La categoria es la que decide en que modulo aparece el comercio: dentro
     * de "Comercio & Gastronomia" solo se listan los prestadores de esa
     * categoria. Solo se completan las categorias vacias, asi una asignacion
     * hecha desde el panel de Usuarios nunca se pisa.
     */
    private function assignProviderRubros(): void
    {
        $rubros = [
            'Comercio & Gastronomía' => [
                'Pizzería Los Hermanos',
                'La Rotisería de Mario',
                'Kiosco El Sol',
                'Verdulería La Fresca',
                'Panadería La Artesana',
            ],
            'Servicios del Hogar & Bienestar' => [
                'AC Solutions - Aire Acondicionado',
                'Plomería Express 24hs',
                'Ferretería San Martín',
                'Color Total Pinturas',
                'Servicios Eléctricos Profesionales',
                'Servicios de Gas Juan',
                'Servicio Técnico Multimarca',
            ],
            'Auxilio Vial & Mecánica' => [
                'Gomería Don Pepe',
                'Grúa y Asistencia Vial 24hs',
                'Mecánica General Carlos',
            ],
            'Abastos Recurrentes' => [
                'Agua Purificada Don Agua',
            ],
        ];

        foreach ($rubros as $rubro => $businessNames) {
            $group = Group::firstOrCreate(['description' => $rubro]);

            Provider::whereIn('business_name', $businessNames)
                ->whereNull('category_id')
                ->update(['category_id' => $group->id]);
        }
    }

    /**
     * Horarios en el formato que espera la app: claves mon..sun con open y shifts
     */
    private function businessHours(): array
    {
        $day = fn (string $from, string $to, bool $open = true) => [
            'open' => $open,
            'shifts' => $open ? [['from' => $from, 'to' => $to]] : [],
        ];

        return [
            'mon' => $day('09:00', '23:30'),
            'tue' => $day('09:00', '23:30'),
            'wed' => $day('09:00', '23:30'),
            'thu' => $day('09:00', '23:30'),
            'fri' => $day('09:00', '23:30'),
            'sat' => $day('09:00', '23:30'),
            'sun' => $day('12:00', '23:00'),
        ];
    }

    /**
     * Cliente de prueba con dirección de entrega
     *
     * @return array{0: \App\Models\User, 1: \App\Models\Address}
     */
    private function seedCustomer(): array
    {
        $cliente = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $activo = UserStatus::firstOrCreate(['status' => 'Activo']);

        $user = User::updateOrCreate(
            ['email' => self::CUSTOMER_EMAIL],
            [
                'name' => 'Ana Prueba',
                'password' => 'password',
                'user_status_id' => $activo->id,
                'type_user_id' => $cliente->id,
                'email_verified_at' => now(),
            ]
        );

        $address = Address::updateOrCreate(
            [
                'user_id' => $user->id,
                'street' => 'Av. Santa Fe',
                'number' => '1234',
            ],
            [
                'country_id' => Country::first()?->id,
                'province_id' => Region::where('type', 'province')->first()?->id,
                'department_id' => Region::where('type', 'department')->first()?->id,
                'region_id' => Region::where('type', 'locality')->first()?->id,
                'postal_code' => 'C1425',
                'floor_apartment' => 'Depto 2B',
                'notes' => 'Timbre 2B, portón negro.',
                'is_primary' => true,
                'sort_order' => 1,
            ]
        );

        return [$user, $address];
    }

    /**
     * Catálogo: 2 categorías, 2 productos con variantes y grupos de opciones
     */
    private function seedCatalog(Provider $provider): array
    {
        $pizzas = Category::updateOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Pizzas Tradicionales'],
            ['sort_order' => 1, 'is_active' => true]
        );

        $empanadas = Category::updateOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Empanadas'],
            ['sort_order' => 2, 'is_active' => true]
        );

        // --- Pizza Muzzarella ---
        $muzzarella = Product::updateOrCreate(
            ['provider_id' => $provider->id, 'category_id' => $pizzas->id, 'name' => 'Pizza Muzzarella'],
            [
                'description' => 'Masa madre, salsa de tomate, muzzarella y orégano.',
                'price' => 8500.00,
                'image_path' => 'productos/pizza-muzzarella.jpg',
                'is_available' => true,
                'sort_order' => 1,
            ]
        );

        $muzzarella->variants()->delete();
        ProductVariant::create(['product_id' => $muzzarella->id, 'name' => 'Personal', 'price' => 7500.00, 'is_available' => false]);
        $muzzarellaGrande = ProductVariant::create(['product_id' => $muzzarella->id, 'name' => 'Grande', 'price' => 9800.00, 'is_available' => true]);
        $muzzarellaFamiliar = ProductVariant::create(['product_id' => $muzzarella->id, 'name' => 'Familiar', 'price' => 12500.00, 'is_available' => true]);

        $agregados = ProductOptionGroup::updateOrCreate(
            ['product_id' => $muzzarella->id, 'name' => 'Agregados'],
            ['min_choices' => 0, 'max_choices' => 3, 'is_required' => false]
        );
        $this->seedOptions($agregados, [
            ['Fainá', 1200.00, true],
            ['Queso extra', 900.00, true],
            ['Jamón cocido', 1500.00, true],
            ['Aceitunas', 800.00, true],
            ['Anchoas', 1800.00, false],
        ]);

        $bebidas = ProductOptionGroup::updateOrCreate(
            ['product_id' => $muzzarella->id, 'name' => 'Bebidas'],
            ['min_choices' => 0, 'max_choices' => 2, 'is_required' => false]
        );
        $this->seedOptions($bebidas, [
            ['Gaseosa 500ml', 1500.00, true],
            ['Agua mineral 500ml', 1000.00, true],
            ['Gaseosa 1.25L', 2200.00, false],
        ]);

        // --- Empanada de Carne ---
        $empanada = Product::updateOrCreate(
            ['provider_id' => $provider->id, 'category_id' => $empanadas->id, 'name' => 'Empanada de Carne'],
            [
                'description' => 'Masa casera, carne picada acharneada y aceitunas.',
                'price' => 1800.00,
                'image_path' => 'productos/empanada-carne.jpg',
                'is_available' => true,
                'sort_order' => 1,
            ]
        );

        $empanada->variants()->delete();
        ProductVariant::create(['product_id' => $empanada->id, 'name' => 'Unidad', 'price' => 1800.00, 'is_available' => true]);
        ProductVariant::create(['product_id' => $empanada->id, 'name' => 'Media docena', 'price' => 9800.00, 'is_available' => true]);
        $empanadaDocena = ProductVariant::create(['product_id' => $empanada->id, 'name' => 'Docena', 'price' => 19000.00, 'is_available' => true]);

        $extras = ProductOptionGroup::updateOrCreate(
            ['product_id' => $empanada->id, 'name' => 'Extras'],
            ['min_choices' => 0, 'max_choices' => 2, 'is_required' => false]
        );
        $this->seedOptions($extras, [
            ['Limón', 0.00, true],
            ['Alioli casero', 500.00, true],
            ['Salsa picante', 300.00, true],
        ]);

        return [
            'muzzarella' => $muzzarella->fresh(),
            'muzzarella_familiar' => $muzzarellaFamiliar,
            'muzzarella_grande' => $muzzarellaGrande,
            'empanada' => $empanada->fresh(),
            'empanada_docena' => $empanadaDocena,
            'faina' => $agregados->options()->where('name', 'Fainá')->first(),
            'queso_extra' => $agregados->options()->where('name', 'Queso extra')->first(),
            'alioli' => $extras->options()->where('name', 'Alioli casero')->first(),
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: float, 2: bool}>  $options
     */
    private function seedOptions(ProductOptionGroup $group, array $options): void
    {
        foreach ($options as [$name, $extraPrice, $isAvailable]) {
            ProductOption::updateOrCreate(
                ['option_group_id' => $group->id, 'name' => $name],
                ['extra_price' => $extraPrice, 'is_available' => $isAvailable]
            );
        }
    }

    /**
     * Pedido de prueba con sus items, opciones y pago
     */
    private function seedOrder(Provider $provider, User $customer, Address $address, array $catalog): void
    {
        $this->createDemoOrder(self::ORDER_NUMBER, $provider, $customer, $address, [
            [
                'product' => $catalog['muzzarella'],
                'variant' => $catalog['muzzarella_familiar'],
                'quantity' => 2,
                'options' => array_filter([$catalog['faina'], $catalog['queso_extra']]),
            ],
            [
                'product' => $catalog['empanada'],
                'variant' => $catalog['empanada_docena'],
                'quantity' => 1,
                'options' => array_filter([$catalog['alioli']]),
            ],
        ], [
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => 'transfer',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_payload' => [
                'banco' => 'Banco Galicia',
                'alias' => 'pideaca.donpepe',
            ],
            'notes' => 'Entregar antes de las 21:30. Tocar el timbre 2B.',
            'discount' => self::DISCOUNT,
        ]);
    }

    /**
     * Crea (o recrea) un pedido de demo con sus items, opciones y pago.
     *
     * Los precios salen siempre del catálogo, igual que en producción.
     *
     * @param  array<int, array{product: Product, variant?: ProductVariant|null, quantity: int, options?: array<int, ProductOption|null>}>  $lines
     * @param  array{status?: string, payment_method?: string, payment_status?: string, payment_gateway?: string, payment_payload?: array, notes?: string|null, discount?: float}  $config
     */
    private function createDemoOrder(
        string $orderNumber,
        Provider $provider,
        User $customer,
        Address $address,
        array $lines,
        array $config = []
    ): Order {
        $order = Order::firstOrNew(['order_number' => $orderNumber]);

        $order->fill([
            'provider_id' => $provider->id,
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => $config['status'] ?? Order::STATUS_PENDING,
            'payment_method' => $config['payment_method'] ?? 'transfer',
            'notes' => $config['notes'] ?? null,
        ]);
        $order->save();

        // Se regeneran en cada corrida para que el seeder siempre deje el mismo pedido.
        $order->items()->delete();
        $order->payments()->delete();

        $subtotal = 0.0;

        foreach ($lines as $line) {
            $product = $line['product'];
            $variant = $line['variant'] ?? null;
            $quantity = (int) $line['quantity'];
            $options = array_values(array_filter($line['options'] ?? []));

            $unitPrice = (float) ($variant ? $variant->price : $product->price);
            $optionsTotal = collect($options)->sum(fn (ProductOption $option) => (float) $option->extra_price);
            $lineTotal = ($unitPrice + $optionsTotal) * $quantity;

            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variant?->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
            ]);

            foreach ($options as $option) {
                OrderItemOption::create([
                    'order_item_id' => $item->id,
                    'group_name' => $option->optionGroup->name,
                    'option_name' => $option->name,
                    'extra_price' => $option->extra_price,
                ]);
            }

            $subtotal += $lineTotal;
        }

        $discount = (float) ($config['discount'] ?? 0);

        $order->subtotal = $subtotal;
        $order->delivery_fee = self::DELIVERY_FEE;
        $order->discount = $discount;
        $order->total = $subtotal + self::DELIVERY_FEE - $discount;
        $order->save();

        Payment::create([
            'order_id' => $order->id,
            'provider' => $config['payment_gateway'] ?? 'transferencia_bancaria',
            'transaction_id' => null,
            'status' => $config['payment_status'] ?? Payment::STATUS_PENDING,
            'amount' => $order->total,
            'payload' => array_merge($config['payment_payload'] ?? [], [
                'origen' => 'FastDeliverySeeder',
            ]),
        ]);

        return $order;
    }

    /**
     * Catálogo y pedidos de demo para "Pizzería Los Hermanos", el comercio del
     * prestador con el que suele entrar el usuario.
     *
     * Solo se llena si ese comercio está vacío: nunca pisa un catálogo cargado.
     */
    private function seedLosHermanosDemo(): void
    {
        $user = User::where('email', self::LOS_HERMANOS_EMAIL)->first();
        $provider = $user ? Provider::where('user_id', $user->id)->first() : null;

        if (! $provider) {
            return;
        }

        if ($provider->products()->exists()) {
            $this->command?->info('Los Hermanos ya tiene catálogo cargado: no se modificó.');

            return;
        }

        $catalog = $this->seedLosHermanosCatalog($provider);

        $customer = User::where('email', self::CUSTOMER_EMAIL)->first();
        $address = $customer?->addresses()->orderBy('id')->first();

        if ($customer && $address) {
            $this->seedLosHermanosOrders($provider, $customer, $address, $catalog);
        }

        $this->command?->info('Fast Delivery: catálogo y pedidos de demo cargados en Pizzería Los Hermanos.');
    }

    /**
     * @return array<string, Product|ProductVariant|ProductOption|null>
     */
    private function seedLosHermanosCatalog(Provider $provider): array
    {
        $pizzas = Category::updateOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Pizzas a la Piedra'],
            ['sort_order' => 1, 'is_active' => true]
        );

        $bebidas = Category::updateOrCreate(
            ['provider_id' => $provider->id, 'name' => 'Bebidas'],
            ['sort_order' => 2, 'is_active' => true]
        );

        // --- Pizza Fugazzeta ---
        $fugazzeta = Product::updateOrCreate(
            ['provider_id' => $provider->id, 'category_id' => $pizzas->id, 'name' => 'Pizza Fugazzeta'],
            [
                'description' => 'Cebolla dulce en abundancia, muzzarella y orégano.',
                'price' => 9500.00,
                'image_path' => null,
                'is_available' => true,
                'sort_order' => 1,
            ]
        );

        $fugazzeta->variants()->delete();
        ProductVariant::create(['product_id' => $fugazzeta->id, 'name' => 'Individual', 'price' => 6500.00, 'is_available' => true]);
        ProductVariant::create(['product_id' => $fugazzeta->id, 'name' => 'Grande', 'price' => 11500.00, 'is_available' => true]);
        $familiar = ProductVariant::create(['product_id' => $fugazzeta->id, 'name' => 'Familiar', 'price' => 15500.00, 'is_available' => true]);

        $agregados = ProductOptionGroup::updateOrCreate(
            ['product_id' => $fugazzeta->id, 'name' => 'Agregados'],
            ['min_choices' => 0, 'max_choices' => 3, 'is_required' => false]
        );
        $this->seedOptions($agregados, [
            ['Muzzarella extra', 900.00, true],
            ['Jamón crudo', 2400.00, true],
            ['Palmitos', 1800.00, true],
            ['Anchoas', 1500.00, false],
        ]);

        // --- Pizza Napolitana ---
        $napolitana = Product::updateOrCreate(
            ['provider_id' => $provider->id, 'category_id' => $pizzas->id, 'name' => 'Pizza Napolitana'],
            [
                'description' => 'Salsa de tomate, muzzarella, tomate fresco y albahaca.',
                'price' => 8900.00,
                'image_path' => null,
                'is_available' => true,
                'sort_order' => 2,
            ]
        );

        $napolitana->variants()->delete();
        ProductVariant::create(['product_id' => $napolitana->id, 'name' => 'Individual', 'price' => 5900.00, 'is_available' => true]);
        $napolitanaGrande = ProductVariant::create(['product_id' => $napolitana->id, 'name' => 'Grande', 'price' => 10900.00, 'is_available' => true]);

        $extras = ProductOptionGroup::updateOrCreate(
            ['product_id' => $napolitana->id, 'name' => 'Extras'],
            ['min_choices' => 0, 'max_choices' => 2, 'is_required' => false]
        );
        $this->seedOptions($extras, [
            ['Muzzarella extra', 900.00, true],
            ['Champiñones', 1200.00, true],
        ]);

        // --- Coca Cola ---
        $coca = Product::updateOrCreate(
            ['provider_id' => $provider->id, 'category_id' => $bebidas->id, 'name' => 'Coca Cola'],
            [
                'description' => 'Gaseosa fría.',
                'price' => 1900.00,
                'image_path' => null,
                'is_available' => true,
                'sort_order' => 1,
            ]
        );

        $coca->variants()->delete();
        $coca500 = ProductVariant::create(['product_id' => $coca->id, 'name' => '500 ml', 'price' => 1900.00, 'is_available' => true]);
        ProductVariant::create(['product_id' => $coca->id, 'name' => '1.5 L', 'price' => 3600.00, 'is_available' => true]);

        return [
            'fugazzeta' => $fugazzeta->fresh(),
            'fugazzeta_familiar' => $familiar,
            'napolitana' => $napolitana->fresh(),
            'napolitana_grande' => $napolitanaGrande,
            'coca' => $coca->fresh(),
            'coca_500' => $coca500,
            'muzzarella_extra' => $agregados->options()->where('name', 'Muzzarella extra')->first(),
        ];
    }

    /**
     * @param  array<string, Product|ProductVariant|ProductOption|null>  $catalog
     */
    private function seedLosHermanosOrders(Provider $provider, User $customer, Address $address, array $catalog): void
    {
        // Recién recibido: es el que el prestador ve como pendiente en su panel.
        $this->createDemoOrder('PIDE-2026-0002', $provider, $customer, $address, [
            [
                'product' => $catalog['fugazzeta'],
                'variant' => $catalog['fugazzeta_familiar'],
                'quantity' => 1,
                'options' => [$catalog['muzzarella_extra']],
            ],
        ], [
            'status' => Order::STATUS_PENDING,
            'notes' => 'Sin cebolla si se puede. Llamar al tocar el timbre.',
        ]);

        // Histórico: sirve para ver un pedido finalizado y el pago acreditado.
        $this->createDemoOrder('PIDE-2026-0003', $provider, $customer, $address, [
            [
                'product' => $catalog['napolitana'],
                'variant' => $catalog['napolitana_grande'],
                'quantity' => 2,
                'options' => [],
            ],
            [
                'product' => $catalog['coca'],
                'variant' => $catalog['coca_500'],
                'quantity' => 2,
                'options' => [],
            ],
        ], [
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Payment::STATUS_PAID,
            'payment_gateway' => 'mercadopago',
            'payment_payload' => ['metodo' => 'tarjeta'],
        ]);
    }
}

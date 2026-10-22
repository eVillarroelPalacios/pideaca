<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithServiceOrders;
use App\Models\Favorite;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceType;
use App\Models\SubGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class ServiceBookingController extends Controller
{
    use InteractsWithServiceOrders;

    // ------------------------------------------------ catalogo de servicios

    /**
     * GET /api/v1/service-subgroups
     *
     * Catalogo del Modulo B para armar el formulario de solicitud: los
     * SubGrupos del grupo "Servicios del Hogar & Bienestar" con cuantos
     * profesionales los ofrecen (vienen del pool de provider_subgroups).
     */
    public function getServiceSubgroups(): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        try {
            $subgroups = SubGroup::query()
                ->whereIn('id', $this->serviceSubgroupIds())
                ->orderBy('description')
                ->withCount('providers')
                ->get()
                ->map(fn (SubGroup $subgroup) => [
                    'id' => $subgroup->id,
                    'description' => $subgroup->description,
                    'group_id' => $subgroup->group_id,
                    'providers_count' => (int) $subgroup->providers_count,
                ])
                ->values();
        } catch (Throwable $e) {
            Log::error('Catalogo: no se pudieron cargar los subgrupos: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el catalogo de servicios. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subgroups' => $subgroups,
            ],
        ]);
    }

    /**
     * GET /api/v1/service-providers
     *
     * Browse de prestadores para Solicitar Servicio con el mismo esquema que
     * los comercios: el cliente busca y elige una tarjeta de prestador. Devuelve
     * los profesionales activos del grupo "Servicios del Hogar & Bienestar",
     * filtrables por texto, por tipo de servicio y por el tab de favoritos.
     */
    public function getServiceProviders(Request $request): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in([
                ServiceType::CODE_EMERGENCY,
                ServiceType::CODE_SCHEDULED,
                ServiceType::CODE_QUOTE,
            ])],
            'search' => ['nullable', 'string', 'max:100'],
            'favorite' => ['sometimes', 'boolean'],
        ]);

        $search = trim((string) ($data['search'] ?? ''));
        $favoriteIds = Favorite::where('user_id', $customer->id)->pluck('provider_id');

        try {
            $base = Provider::query()
                ->where('is_active', true)
                ->whereHas('subgroups.group', fn ($q) => $q->where('description', self::SERVICE_GROUP_NAME))
                ->when(! empty($data['type']), function ($query) use ($data) {
                    $query->whereHas('serviceTypes', fn ($q) => $q->where('code', $data['type']));
                })
                ->when($search !== '', function ($query) use ($search) {
                    $lower = mb_strtolower($search);
                    $query->where(function ($q) use ($lower) {
                        $q->whereRaw('LOWER(business_name) LIKE ?', ['%'.$lower.'%'])
                            ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", ['%'.$lower.'%'])
                            ->orWhereRaw("LOWER(COALESCE(zone, '')) LIKE ?", ['%'.$lower.'%']);
                    });
                });

            $total = (clone $base)->count();
            $favoritesCount = (clone $base)->whereIn('id', $favoriteIds)->count();

            $providers = $base
                ->when($request->boolean('favorite'), fn ($query) => $query->whereIn('id', $favoriteIds))
                ->with(['subgroups.group', 'serviceTypes', 'images'])
                ->orderBy('business_name')
                ->get()
                ->map(fn (Provider $provider) => $this->serviceProviderPayload($provider, $favoriteIds))
                ->values();
        } catch (Throwable $e) {
            Log::error('Browse: no se pudieron cargar los prestadores: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron cargar los prestadores. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers,
                'total_count' => $total,
                'favorites_count' => $favoritesCount,
            ],
        ]);
    }

    /**
     * POST /api/v1/customer/service-orders/scheduled
     *
     * Reserva una cita (a domicilio o en el local). El cliente elige un
     * prestador de las tarjetas (flujo directo: la orden se le asigna a el)
     * o un SubGrupo del pool. La orden nace en REQUESTED con el horario
     * elegido; el precio lo define el profesional al aceptar.
     */
    public function bookScheduledService(Request $request): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        $data = $this->serviceOrderData($request, [
            'sub_group_id' => [
                'nullable',
                'required_without:provider_id',
                'integer',
                Rule::exists('sub_groups', 'id')->whereIn('id', $this->serviceSubgroupIds()),
            ],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'fulfillment' => ['required', 'string', 'in:AT_HOME,AT_MERCHANT_LOCATION'],
            'payment_method' => ['required', 'string', 'max:30'],
            'payment_mode' => ['required', 'string', 'in:DEPOSIT,FULL'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Datos de acceso de la cita: direccion estructurada + recordatorio.
            'address_street' => ['nullable', 'string', 'max:150'],
            'address_unit' => ['nullable', 'string', 'max:50'],
            'address_neighborhood' => ['nullable', 'string', 'max:100'],
            'reminder_channel' => ['nullable', 'string', Rule::in(['whatsapp', 'email'])],
        ]);

        $target = $this->resolveServiceOrderTarget($data, ServiceType::CODE_SCHEDULED);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $merchantId = $target['provider_id'];
        $data['sub_group_id'] = $target['sub_group_id'];

        try {
            $order = DB::transaction(function () use ($data, $customer, $merchantId) {
                return ServiceOrder::create([
                    'customer_id' => $customer->id,
                    'sub_group_id' => $data['sub_group_id'],
                    'merchant_id' => $merchantId,
                    'order_type' => ServiceOrder::TYPE_SCHEDULED,
                    'status' => ServiceOrder::STATUS_REQUESTED,
                    'scheduled_at' => $data['scheduled_at'],
                    'fulfillment' => $data['fulfillment'],
                    'payment_method' => $data['payment_method'],
                    'payment_mode' => $data['payment_mode'],
                    'deposit_amount' => 0,
                    'visit_fee' => 0,
                    'additional_cost' => 0,
                    'total_amount' => 0,
                    'escrow_status' => ServiceOrder::ESCROW_HOLDING,
                    'description' => $data['notes'] ?? null,
                    'address_street' => $data['address_street'] ?? null,
                    'address_unit' => $data['address_unit'] ?? null,
                    'address_neighborhood' => $data['address_neighborhood'] ?? null,
                    'address' => $this->composeScheduledAddress($data),
                    'reminder_channel' => $data['reminder_channel'] ?? null,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Cita: no se pudo reservar el turno: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo reservar la cita. Intenta nuevamente.',
            ], 500);
        }

        $this->processBookingPayment($order);

        return response()->json([
            'success' => true,
            'message' => $merchantId !== null
                ? 'Cita solicitada. El prestador elegido va a confirmarte el precio.'
                : 'Cita solicitada. Un profesional del rubro va a aceptarla y confirmarte el precio.',
            'data' => [
                'order' => $this->orderPayload($order),
                'payment' => [
                    'mode' => $order->payment_mode,
                    'method' => $order->payment_method,
                    'charged' => $order->deposit_amount,
                    'total' => $order->total_amount,
                    'remaining' => number_format(round((float) $order->total_amount - (float) $order->deposit_amount, 2), 2, '.', ''),
                    'note' => 'El cobro se registra cuando el profesional carga el precio del trabajo.',
                ],
            ],
        ], 201);
    }

    /**
     * POST /api/v1/customer/service-orders/quote
     *
     * Solicitud de presupuesto: el cliente elige un prestador de las tarjetas
     * (flujo directo) o un SubGrupo del pool, con detalles, fotos y video
     * opcional del trabajo. La orden nace en REQUESTED; el profesional elegido
     * (o los del rubro) responden con sus propuestas.
     */
    public function requestQuote(Request $request): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        $data = $this->serviceOrderData($request, [
            'sub_group_id' => [
                'nullable',
                'required_without:provider_id',
                'integer',
                Rule::exists('sub_groups', 'id')->whereIn('id', $this->serviceSubgroupIds()),
            ],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'details' => ['required', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1', 'max:'.(int) config('bookings.max_quote_photos', 5)],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,m4v,webm,mov,qt', 'max:20480'],
            'quote_context' => ['nullable', 'string', 'max:5000'],
        ]);

        // El contexto dinamico llega como string JSON; lo decodificamos y
        // validamos la forma interna (rubro, atajos y respuestas por pregunta).
        $quoteContext = $this->decodeQuoteContext($data['quote_context'] ?? null);

        if ($quoteContext instanceof JsonResponse) {
            return $quoteContext;
        }

        $target = $this->resolveServiceOrderTarget($data, ServiceType::CODE_QUOTE);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $merchantId = $target['provider_id'];
        $data['sub_group_id'] = $target['sub_group_id'];

        try {
            $order = DB::transaction(function () use ($data, $customer, $merchantId, $quoteContext) {
                return ServiceOrder::create([
                    'customer_id' => $customer->id,
                    'sub_group_id' => $data['sub_group_id'],
                    'merchant_id' => $merchantId,
                    'order_type' => ServiceOrder::TYPE_QUOTE,
                    'status' => ServiceOrder::STATUS_REQUESTED,
                    'visit_fee' => 0,
                    'additional_cost' => 0,
                    'total_amount' => 0,
                    'escrow_status' => ServiceOrder::ESCROW_HOLDING,
                    'description' => $data['details'],
                    'quote_context' => $quoteContext,
                ]);
            });

            $adjuntos = $this->storeQuoteMedia($order, $request);

            // Fila del cliente: guarda los adjuntos del pedido y queda como
            // contenedor de referencias; las propuestas son filas aparte.
            $quote = ServiceQuote::create([
                'service_order_id' => $order->id,
                'provider_id' => null,
                'proposed_price' => 0,
                'attachments_json' => $adjuntos,
                'status' => ServiceQuote::STATUS_PENDING,
            ]);
        } catch (Throwable $e) {
            Log::error('Presupuesto: no se pudo crear la solicitud: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la solicitud de presupuesto. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $merchantId !== null
                ? 'Solicitud de presupuesto enviada. El prestador elegido te va a responder con su propuesta.'
                : 'Solicitud de presupuesto enviada. Los profesionales del rubro te van a responder con sus propuestas.',
            'data' => [
                'order' => $this->orderPayload($order),
                'quote' => $this->quotePayload($quote),
            ],
        ], 201);
    }

    /**
     * POST /api/v1/provider/service-orders/{serviceOrder}/quote-proposal
     *
     * El prestador del rubro responde la solicitud con monto cerrado y
     * tiempo estimado en service_quotes. La orden pasa a QUOTED esperando
     * la decision del cliente.
     */
    public function submitQuoteProposal(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($serviceOrder->order_type !== ServiceOrder::TYPE_QUOTE) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no pertenece al flujo de presupuestos.',
            ], 409);
        }

        if ($serviceOrder->merchant_id !== null && (int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue tomada por otro profesional.',
            ], 409);
        }

        if (! $provider->subgroups()->where('sub_groups.id', $serviceOrder->sub_group_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu comercio no ofrece este servicio.',
            ], 403);
        }

        if (! in_array($serviceOrder->status, [ServiceOrder::STATUS_REQUESTED, ServiceOrder::STATUS_QUOTED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud ya no acepta propuestas.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'proposed_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'estimated_time' => ['required', 'string', 'max:60'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $quote = ServiceQuote::firstOrCreate(
                ['service_order_id' => $serviceOrder->id, 'provider_id' => $provider->id],
                ['attachments_json' => [], 'proposed_price' => 0, 'status' => ServiceQuote::STATUS_PENDING]
            );

            $quote->fill([
                'proposed_price' => $data['proposed_price'],
                'estimated_time' => $data['estimated_time'],
                'details' => $data['details'] ?? $quote->details,
                'status' => ServiceQuote::STATUS_PENDING,
            ]);
            $quote->save();

            if ($serviceOrder->status === ServiceOrder::STATUS_REQUESTED) {
                $serviceOrder->status = ServiceOrder::STATUS_QUOTED;
                $serviceOrder->save();
            }
        } catch (Throwable $e) {
            Log::error('Presupuesto: no se pudo enviar la propuesta de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la propuesta. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propuesta enviada. El cliente debe aceptarla para confirmar el trabajo.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
                'quote' => $this->quotePayload($quote->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/customer/service-orders/{serviceOrder}/accept-quote
     *
     * El cliente acepta una propuesta con 1-click: la propuesta pasa a
     * ACCEPTED, el resto se rechaza, la orden queda confirmada en ACCEPTED
     * con el comercio de esa propuesta y el monto final en total_amount.
     * Acepta un quote opcional en el body; si no viene usa la ultima
     * propuesta pendiente.
     */
    public function acceptQuoteProposal(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        if ($serviceOrder->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
            ], 404);
        }

        if ($serviceOrder->order_type !== ServiceOrder::TYPE_QUOTE) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no pertenece al flujo de presupuestos.',
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un presupuesto pendiente de aceptacion en esta orden.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'quote' => ['nullable', 'integer', 'exists:service_quotes,id'],
        ]);

        $quote = isset($data['quote'])
            ? $serviceOrder->quotes()->whereKey($data['quote'])->first()
            : $serviceOrder->quotes()->whereNotNull('provider_id')->latest('id')->first();

        if (! $quote
            || $quote->status !== ServiceQuote::STATUS_PENDING
            || (float) $quote->proposed_price <= 0
            || ! $quote->provider_id) {
            return response()->json([
                'success' => false,
                'message' => 'La propuesta de este presupuesto ya no esta disponible.',
            ], 409);
        }

        if (! $serviceOrder->isEscrowHolding()) {
            return response()->json([
                'success' => false,
                'message' => 'Los fondos de esta orden ya no estan retenidos en garantia.',
            ], 409);
        }

        $deposit = $serviceOrder->payment_mode === 'FULL'
            ? (float) $quote->proposed_price
            : round((float) $quote->proposed_price * (float) config('bookings.deposit_percent', 50) / 100, 2);

        try {
            if (! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_ACCEPTED)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no se puede confirmar desde su estado actual.',
                ], 409);
            }

            DB::transaction(function () use ($serviceOrder, $quote, $deposit) {
                $serviceOrder->quotes()
                    ->whereKeyNot($quote->id)
                    ->where('status', ServiceQuote::STATUS_PENDING)
                    ->update(['status' => ServiceQuote::STATUS_REJECTED]);

                $quote->status = ServiceQuote::STATUS_ACCEPTED;
                $quote->save();

                $serviceOrder->status = ServiceOrder::STATUS_ACCEPTED;
                $serviceOrder->merchant_id = $quote->provider_id;
                $serviceOrder->total_amount = $quote->proposed_price;
                $serviceOrder->deposit_amount = $deposit;
                $serviceOrder->accepted_at = now();
                $serviceOrder->save();
            });

            $this->processBookingPayment($serviceOrder);
        } catch (Throwable $e) {
            Log::error('Presupuesto: no se pudo aceptar la propuesta de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo aceptar la propuesta. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Presupuesto aceptado. El trabajo queda confirmado.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
                'quote' => $this->quotePayload($quote->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/customer/service-orders/{serviceOrder}/reject-quote
     *
     * El cliente rechaza la propuesta vigente: la cotizacion pasa a REJECTED
     * y la orden sigue en QUOTED; los demas profesionales pueden seguir
     * mandando propuestas.
     */
    public function rejectQuote(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        if ($serviceOrder->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
            ], 404);
        }

        if ($serviceOrder->order_type !== ServiceOrder::TYPE_QUOTE) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no pertenece al flujo de presupuestos.',
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un presupuesto pendiente de decision en esta orden.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'quote' => ['nullable', 'integer', 'exists:service_quotes,id'],
        ]);

        $quote = isset($data['quote'])
            ? $serviceOrder->quotes()->whereKey($data['quote'])->first()
            : $serviceOrder->quotes()->whereNotNull('provider_id')->latest('id')->first();

        if (! $quote
            || $quote->status !== ServiceQuote::STATUS_PENDING
            || (float) $quote->proposed_price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La propuesta de este presupuesto ya no esta disponible.',
            ], 409);
        }

        try {
            $quote->status = ServiceQuote::STATUS_REJECTED;
            $quote->save();
        } catch (Throwable $e) {
            Log::error('Presupuesto: no se pudo rechazar la propuesta de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo rechazar la propuesta. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propuesta rechazada. Los demas profesionales pueden enviar nuevas.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
                'quote' => $this->quotePayload($quote->fresh()),
            ],
        ]);
    }

    // ------------------------------------------------------------- helpers

    /**
     * Gateway manual (mismo criterio que las suscripciones): la seña o el
     * pago total quedan registrados en la orden y el enganche con la
     * pasarela se resuelve cuando el PSP este disponible.
     */
    private function processBookingPayment(ServiceOrder $order): void
    {
        Log::info('Cita #'.$order->id.': '.$order->payment_mode.' por $'
            .number_format((float) $order->deposit_amount, 2, ',', '.')
            .' via '.$order->payment_method.' (gateway manual).');
    }

    /**
     * Decodifica y valida el contexto dinamico del presupuesto (rubro, atajos
     * y respuestas). Devuelve array listo para persistir, null si no vino, o
     * un JsonResponse 422 si la forma es invalida.
     *
     * @return array<string, mixed>|null|JsonResponse
     */
    private function decodeQuoteContext(?string $raw): array|null|JsonResponse
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return $this->invalidQuoteContext('El contexto del presupuesto no es un JSON válido.');
        }

        $rubric = $decoded['rubric'] ?? null;
        $tags = $decoded['tags'] ?? [];
        $answers = $decoded['answers'] ?? [];
        $availability = $decoded['availability'] ?? null;
        $quality = $decoded['quality'] ?? null;

        if ($rubric !== null && (! is_string($rubric) || mb_strlen($rubric) > 40)) {
            return $this->invalidQuoteContext('El rubro del contexto es inválido.');
        }

        if (! is_array($tags) || count($tags) > 10) {
            return $this->invalidQuoteContext('Los atajos del contexto son inválidos.');
        }

        foreach ($tags as $tag) {
            if (! is_string($tag) || mb_strlen($tag) > 200) {
                return $this->invalidQuoteContext('Cada atajo debe ser un texto de hasta 200 caracteres.');
            }
        }

        if (! is_array($answers)) {
            return $this->invalidQuoteContext('Las respuestas del contexto son inválidas.');
        }

        foreach ($answers as $key => $answer) {
            if (! is_string($key) || mb_strlen($key) > 60) {
                return $this->invalidQuoteContext('La clave de una respuesta es inválida.');
            }
            if (! is_array($answer) || ! isset($answer['value']) || ! is_string($answer['value']) || mb_strlen($answer['value']) > 120) {
                return $this->invalidQuoteContext('El valor de una respuesta es inválido.');
            }
        }

        $availability = $this->normalizeQuoteAvailability($availability);

        if ($availability instanceof JsonResponse) {
            return $availability;
        }

        $quality = $this->normalizeQuoteQuality($quality);

        if ($quality instanceof JsonResponse) {
            return $quality;
        }

        return [
            'rubric' => $rubric,
            'tags' => array_values($tags),
            'answers' => $answers,
            'availability' => $availability,
            'quality' => $quality,
        ];
    }

    /**
     * Valida la disponibilidad horaria elegida por el cliente: "asap",
     * "manana" o una franja ("ventana") con from/to predefinidos.
     *
     * @param  mixed  $availability
     * @return array<string, mixed>|null|JsonResponse
     */
    private function normalizeQuoteAvailability($availability): array|null|JsonResponse
    {
        if ($availability === null) {
            return null;
        }

        if (! is_array($availability)) {
            return $this->invalidQuoteContext('La disponibilidad del presupuesto es inválida.');
        }

        $mode = $availability['mode'] ?? null;

        if (! is_string($mode) || ! in_array($mode, ['asap', 'manana', 'ventana'], true)) {
            return $this->invalidQuoteContext('La disponibilidad elegida no es válida.');
        }

        $result = ['mode' => $mode, 'label' => null, 'window' => null];

        if ($mode === 'ventana') {
            $window = $availability['window'] ?? null;

            if (! is_array($window) || ! isset($window['from'], $window['to']) || ! is_string($window['from']) || ! is_string($window['to'])) {
                return $this->invalidQuoteContext('La franja horaria elegida es inválida.');
            }

            $result['window'] = ['from' => $window['from'], 'to' => $window['to']];
        }

        if (isset($availability['label']) && is_string($availability['label'])) {
            $result['label'] = mb_substr($availability['label'], 0, 60);
        }

        return $result;
    }

    /**
     * Valida el nivel de detalle de la solicitud (Bajo/Medio/Alto + score).
     *
     * @param  mixed  $quality
     * @return array<string, mixed>|null|JsonResponse
     */
    private function normalizeQuoteQuality($quality): array|null|JsonResponse
    {
        if ($quality === null) {
            return null;
        }

        if (! is_array($quality)) {
            return $this->invalidQuoteContext('El nivel de detalle de la solicitud es inválido.');
        }

        $level = $quality['level'] ?? null;

        if (! is_string($level) || ! in_array($level, ['bajo', 'medio', 'alto'], true)) {
            return $this->invalidQuoteContext('El nivel de detalle elegido no es válido.');
        }

        $score = $quality['score'] ?? 0;

        if (! is_numeric($score)) {
            return $this->invalidQuoteContext('El puntaje del nivel de detalle es inválido.');
        }

        return ['level' => $level, 'score' => max(0, min(100, (int) $score))];
    }

    private function invalidQuoteContext(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => ['quote_context' => [$message]],
        ], 422);
    }

    /**
     * Arma la direccion legible de la cita a partir de los campos
     * estructurados (calle y numero, piso/depto, barrio/zona) para que la
     * columna `address` siga siendo un texto listo para mostrar.
     *
     * @param  array<string, mixed>  $data
     */
    private function composeScheduledAddress(array $data): ?string
    {
        $street = trim((string) ($data['address_street'] ?? ''));
        $unit = trim((string) ($data['address_unit'] ?? ''));
        $neighborhood = trim((string) ($data['address_neighborhood'] ?? ''));

        $parts = array_filter([
            $street !== '' ? $street : null,
            $unit !== '' ? $unit : null,
            $neighborhood !== '' ? $neighborhood : null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Guarda las fotos y el video del trabajo en public/images/servicios.
     *
     * @return array<int, array<string, string>>
     */
    private function storeQuoteMedia(ServiceOrder $order, Request $request): array
    {
        $destDir = public_path('images/servicios');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $adjuntos = [];

        foreach ((array) $request->file('photos', []) as $i => $file) {
            if (! $file) {
                continue;
            }

            $filename = 'quote_'.$order->id.'_foto_'.$i.'.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);
            $adjuntos[] = ['type' => 'photo', 'path' => $filename];
        }

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = 'quote_'.$order->id.'_video.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);
            $adjuntos[] = ['type' => 'video', 'path' => $filename];
        }

        return $adjuntos;
    }

    private function quotePayload(ServiceQuote $quote): array
    {
        $quote->loadMissing(['serviceOrder', 'provider']);

        $adjuntos = collect($quote->attachments())->map(fn (array $adjunto) => [
            'type' => $adjunto['type'] ?? 'photo',
            'url' => isset($adjunto['path']) ? asset('images/servicios/'.$adjunto['path']) : null,
        ])->values()->all();

        return [
            'id' => $quote->id,
            'service_order_id' => $quote->service_order_id,
            'provider_id' => $quote->provider_id,
            'provider' => $quote->provider ? [
                'id' => $quote->provider->id,
                'business_name' => $quote->provider->business_name,
            ] : null,
            'status' => $quote->status,
            'proposed_price' => $quote->proposed_price,
            'details' => $quote->details,
            'estimated_time' => $quote->estimated_time,
            'attachments' => $adjuntos,
            'created_at' => $quote->created_at?->toIso8601String(),
            'updated_at' => $quote->updated_at?->toIso8601String(),
        ];
    }
}

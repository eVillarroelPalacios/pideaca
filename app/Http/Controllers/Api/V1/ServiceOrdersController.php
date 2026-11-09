<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\InteractsWithServiceOrders;
use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceRating;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Services\ServiceEscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Solicitudes de servicio para las apps móviles (Módulo B).
 *
 * Reutiliza las reglas del browse web vía el trait
 * InteractsWithServiceOrders: mismo universo de rubros, misma resolución
 * de prestador directo vs. pool y mismo payload de orden. La app manda
 * JSON salvo las fotos, que viajan como multipart/form-data.
 */
class ServiceOrdersController extends Controller
{
    use InteractsWithServiceOrders;

    public function __construct(private readonly ServiceEscrowService $escrow) {}

    /**
     * Rubros del hogar para armar el formulario de solicitud.
     */
    public function subgroups(): JsonResponse
    {
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
                ]);
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudieron cargar los rubros: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron cargar los rubros. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $subgroups,
            'service_types' => collect(ServiceOrder::ORDER_TYPES)
                ->map(fn (string $code) => [
                    'code' => $code,
                    'name' => ServiceType::query()->where('code', $code)->value('name') ?? $code,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Mis solicitudes, paginadas. Filtros: status (legado o simplificado) y
     * order_type. Incluye los filtros válidos para armar los chips.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_merge(ServiceOrder::STATUSES, ServiceOrder::DISPLAY_STATUSES))],
            'order_type' => ['nullable', 'string', 'in:'.implode(',', ServiceOrder::ORDER_TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $orders = ServiceOrder::query()
            ->where('customer_id', $customer->id)
            ->with(['subGroup', 'merchant', 'quotes.provider', 'ratings'])
            ->when($data['status'] ?? null, fn ($query, $status) => $query->whereIn('status', ServiceOrder::legacyStatusesFor($status)))
            ->when($data['order_type'] ?? null, fn ($query, $type) => $query->ofType($type))
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => collect($orders->items())
                ->map(fn (ServiceOrder $order) => $this->orderPayload($order))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'filters' => [
                'statuses' => ServiceOrder::displayStatuses(),
                'order_types' => ServiceOrder::ORDER_TYPES,
            ],
        ]);
    }

    public function show(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ((int) $serviceOrder->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés acceso a esta solicitud.',
                'code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $this->orderPayload($serviceOrder),
                'statuses' => ServiceOrder::displayStatuses(),
            ],
        ]);
    }

    /**
     * Alta de urgencia 24/7. La foto es obligatoria; el audio, opcional.
     * Todo viaja como multipart/form-data.
     */
    public function storeEmergency(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'sub_group_id' => [
                'nullable',
                'required_without:provider_id',
                'integer',
                Rule::exists('sub_groups', 'id')->whereIn('id', $this->serviceSubgroupIds()),
            ],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'latitude' => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'audio' => ['nullable', 'file', 'mimes:mp3,m4a,ogg,oga,webm,wav,aac', 'max:8192'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'payment_method' => [
                'nullable',
                'string',
                'max:30',
                Rule::in(['efectivo', 'transferencia', 'tarjeta', 'mercado_pago']),
            ],
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $target = $this->resolveServiceOrderTarget($data, ServiceType::CODE_EMERGENCY);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        try {
            $order = DB::transaction(function () use ($data, $request, $target) {
                $order = ServiceOrder::create([
                    'customer_id' => $request->user()->id,
                    'sub_group_id' => $target['sub_group_id'],
                    'merchant_id' => $target['provider_id'],
                    'order_type' => ServiceOrder::TYPE_EMERGENCY,
                    'status' => ServiceOrder::STATUS_REQUESTED,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'escrow_status' => ServiceOrder::ESCROW_HOLDING,
                    'description' => $data['description'] ?? null,
                    'address' => $data['address'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                ]);

                $this->attachEmergencyMedia($order, $request);

                return $order;
            });
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo crear la urgencia: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la solicitud de urgencia. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $target['provider_id'] !== null
                ? 'Urgencia enviada al prestador elegido. Te avisa cuando la acepte.'
                : 'Solicitud de urgencia enviada. Estamos avisando a los profesionales de guardia.',
            'data' => ['order' => $this->orderPayload($order->fresh())],
        ], 201);
    }

    /**
     * Reserva de cita a domicilio o en el local.
     */
    public function storeScheduled(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
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
            'address_street' => ['nullable', 'string', 'max:150'],
            'address_unit' => ['nullable', 'string', 'max:50'],
            'address_neighborhood' => ['nullable', 'string', 'max:100'],
            'reminder_channel' => ['nullable', 'string', Rule::in(['whatsapp', 'email'])],
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $target = $this->resolveServiceOrderTarget($data, ServiceType::CODE_SCHEDULED);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        try {
            $order = DB::transaction(function () use ($data, $request, $target) {
                return ServiceOrder::create([
                    'customer_id' => $request->user()->id,
                    'sub_group_id' => $target['sub_group_id'],
                    'merchant_id' => $target['provider_id'],
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
                    'address' => $this->composeAddress($data),
                    'reminder_channel' => $data['reminder_channel'] ?? null,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo reservar la cita: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo reservar la cita. Intentá nuevamente.',
            ], 500);
        }

        Log::info('Móvil: cita #'.$order->id.' '.$order->payment_mode.' vía '.$order->payment_method.' (gateway manual).');

        return response()->json([
            'success' => true,
            'message' => $target['provider_id'] !== null
                ? 'Cita solicitada. El prestador elegido va a confirmarte el precio.'
                : 'Cita solicitada. Un profesional del rubro va a aceptarla y confirmarte el precio.',
            'data' => ['order' => $this->orderPayload($order)],
        ], 201);
    }

    /**
     * Solicitud de presupuesto con fotos obligatorias (1 a 5) y video
     * opcional. Todo viaja como multipart/form-data.
     */
    public function storeQuote(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
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
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        $target = $this->resolveServiceOrderTarget($data, ServiceType::CODE_QUOTE);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        try {
            $order = DB::transaction(function () use ($data, $request, $target) {
                return ServiceOrder::create([
                    'customer_id' => $request->user()->id,
                    'sub_group_id' => $target['sub_group_id'],
                    'merchant_id' => $target['provider_id'],
                    'order_type' => ServiceOrder::TYPE_QUOTE,
                    'status' => ServiceOrder::STATUS_REQUESTED,
                    'visit_fee' => 0,
                    'additional_cost' => 0,
                    'total_amount' => 0,
                    'escrow_status' => ServiceOrder::ESCROW_HOLDING,
                    'description' => $data['details'],
                ]);
            });

            $attachments = $this->storeQuoteMedia($order, $request);

            $quote = ServiceQuote::create([
                'service_order_id' => $order->id,
                'provider_id' => null,
                'proposed_price' => 0,
                'attachments_json' => $attachments,
                'status' => ServiceQuote::STATUS_PENDING,
            ]);
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo crear la solicitud de presupuesto: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la solicitud de presupuesto. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $target['provider_id'] !== null
                ? 'Solicitud de presupuesto enviada. El prestador elegido te va a responder con su propuesta.'
                : 'Solicitud de presupuesto enviada. Los profesionales del rubro te van a responder con sus propuestas.',
            'data' => ['order' => $this->orderPayload($order->fresh())],
        ], 201);
    }

    public function cancel(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ((int) $serviceOrder->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
                'code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        if (! $serviceOrder->isCancellable()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya no se puede cancelar.',
                'code' => 'ORDER_NOT_CANCELLABLE',
                'status' => $serviceOrder->status,
            ], 409);
        }

        try {
            DB::transaction(function () use ($serviceOrder) {
                $serviceOrder->status = ServiceOrder::STATUS_CANCELLED;
                $serviceOrder->save();
                $this->escrow->refund($serviceOrder);
            });
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo cancelar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cancelar la orden. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada. Los fondos retenidos se devuelven al cliente.',
            'data' => ['order' => $this->orderPayload($serviceOrder->fresh())],
        ]);
    }

    /**
     * El cliente acepta una propuesta: esa pasa a ACCEPTED, el resto se
     * rechaza y la orden queda confirmada en ACCEPTED con el comercio de la
     * propuesta (igual que la web). Acepta `quote` opcional; si no viene usa
     * la última propuesta pendiente.
     */
    public function acceptQuote(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $ownership = $this->ownOrder($request, $serviceOrder);

        if ($ownership instanceof JsonResponse) {
            return $ownership;
        }

        if ($serviceOrder->order_type !== ServiceOrder::TYPE_QUOTE) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no pertenece al flujo de presupuestos.',
                'code' => 'NOT_A_QUOTE',
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un presupuesto pendiente de aceptación en esta orden.',
                'code' => 'NO_PENDING_QUOTE',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate(['quote' => ['nullable', 'integer', 'exists:service_quotes,id']]);

        $quote = isset($data['quote'])
            ? $serviceOrder->quotes()->whereKey($data['quote'])->first()
            : $serviceOrder->quotes()->whereNotNull('provider_id')->latest('id')->first();

        if (! $quote
            || $quote->status !== ServiceQuote::STATUS_PENDING
            || (float) $quote->proposed_price <= 0
            || ! $quote->provider_id) {
            return response()->json([
                'success' => false,
                'message' => 'La propuesta de este presupuesto ya no está disponible.',
                'code' => 'QUOTE_UNAVAILABLE',
            ], 409);
        }

        if (! $serviceOrder->isEscrowHolding()) {
            return response()->json([
                'success' => false,
                'message' => 'Los fondos de esta orden ya no están retenidos en garantía.',
                'code' => 'ESCROW_NOT_HOLDING',
            ], 409);
        }

        if (! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_ACCEPTED)) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no se puede confirmar desde su estado actual.',
                'code' => 'INVALID_TRANSITION',
            ], 409);
        }

        $deposit = $serviceOrder->payment_mode === 'FULL'
            ? (float) $quote->proposed_price
            : round((float) $quote->proposed_price * (float) config('bookings.deposit_percent', 50) / 100, 2);

        try {
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

            Log::info('Móvil: orden #'.$serviceOrder->id.' confirmada por $'
                .number_format((float) $serviceOrder->deposit_amount, 2, ',', '.')
                .' (gateway manual).');
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo aceptar la propuesta de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo aceptar la propuesta. Intentá nuevamente.',
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
     * El cliente rechaza una propuesta: pasa a REJECTED y la orden sigue en
     * QUOTED para que otros profesionales puedan seguir ofertando.
     */
    public function rejectQuote(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $ownership = $this->ownOrder($request, $serviceOrder);

        if ($ownership instanceof JsonResponse) {
            return $ownership;
        }

        if ($serviceOrder->order_type !== ServiceOrder::TYPE_QUOTE) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no pertenece al flujo de presupuestos.',
                'code' => 'NOT_A_QUOTE',
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un presupuesto pendiente de decisión en esta orden.',
                'code' => 'NO_PENDING_QUOTE',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate(['quote' => ['nullable', 'integer', 'exists:service_quotes,id']]);

        $quote = isset($data['quote'])
            ? $serviceOrder->quotes()->whereKey($data['quote'])->first()
            : $serviceOrder->quotes()->whereNotNull('provider_id')->latest('id')->first();

        if (! $quote
            || $quote->status !== ServiceQuote::STATUS_PENDING
            || (float) $quote->proposed_price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La propuesta de este presupuesto ya no está disponible.',
                'code' => 'QUOTE_UNAVAILABLE',
            ], 409);
        }

        try {
            $quote->status = ServiceQuote::STATUS_REJECTED;
            $quote->save();
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo rechazar la propuesta de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo rechazar la propuesta. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propuesta rechazada. Los demás profesionales pueden enviar nuevas.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
                'quote' => $this->quotePayload($quote->fresh()),
            ],
        ]);
    }

    /**
     * Calificación bilateral (igual que la web): el cliente califica al
     * prestador y el prestador al cliente, solo en trabajos completados y
     * una sola vez por usuario.
     */
    public function rate(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $user = $request->user();
        $isCustomer = (int) $serviceOrder->customer_id === (int) $user->id;
        $provider = $user->provider;
        $isProvider = $provider && (int) $serviceOrder->merchant_id === (int) $provider->id;

        if (! $isCustomer && ! $isProvider) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
                'code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden calificar trabajos completados.',
                'code' => 'ORDER_NOT_COMPLETED',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($isCustomer && ! $serviceOrder->merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden todavía no tiene prestador asignado.',
                'code' => 'NO_MERCHANT',
            ], 409);
        }

        $rateeId = $isCustomer ? $serviceOrder->merchant->user_id : $serviceOrder->customer_id;

        if (ServiceRating::where('service_order_id', $serviceOrder->id)
            ->where('reviewer_id', $user->id)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya calificaste este trabajo.',
                'code' => 'ALREADY_RATED',
            ], 409);
        }

        try {
            $rating = ServiceRating::create([
                'service_order_id' => $serviceOrder->id,
                'reviewer_id' => $user->id,
                'ratee_id' => $rateeId,
                'stars' => $data['stars'],
                'comment' => $data['comment'] ?? null,
            ]);

            $providerRating = null;

            if ($isCustomer) {
                $average = ServiceRating::where('ratee_id', $rateeId)->avg('stars');
                $serviceOrder->merchant->rating = round((float) $average, 2);
                $serviceOrder->merchant->save();
                $providerRating = $serviceOrder->merchant->rating;
            }
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo calificar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la calificación. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $isCustomer
                ? 'Gracias por calificar al prestador.'
                : 'Gracias por calificar al cliente.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
                'rating' => [
                    'id' => $rating->id,
                    'service_order_id' => $rating->service_order_id,
                    'reviewer_id' => $rating->reviewer_id,
                    'ratee_id' => $rating->ratee_id,
                    'stars' => $rating->stars,
                    'comment' => $rating->comment,
                    'created_at' => $rating->created_at?->toIso8601String(),
                ],
                'provider_rating' => $providerRating,
            ],
        ]);
    }

    /**
     * La orden pertenece al usuario logueado o devolvemos 404 (igual que la
     * web: no se revela si existe una orden ajena).
     *
     * @return null|JsonResponse
     */
    private function ownOrder(Request $request, ServiceOrder $serviceOrder)
    {
        if ((int) $serviceOrder->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
                'code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        return null;
    }

    /**
     * Propuesta individual para las respuestas de aceptar/rechazar.
     */
    private function quotePayload(ServiceQuote $quote): array
    {
        $quote->loadMissing('provider');

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
            'created_at' => $quote->created_at?->toIso8601String(),
        ];
    }

    /**
     * El cliente autoriza el precio cargado por el prestador: la orden pasa
     * a IN_PROGRESS y el importe retenido queda en el estimado aprobado.
     */
    public function approveEstimate(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ((int) $serviceOrder->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
                'code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_EMERGENCY, ServiceOrder::TYPE_SCHEDULED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no usa estimado en el lugar.',
                'code' => 'NO_ESTIMATE_FLOW'
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un estimado pendiente de aprobación en esta orden.',
                'code' => 'NO_PENDING_ESTIMATE',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if ((float) $serviceOrder->additional_cost <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no tiene costos adicionales pendientes.',
                'code' => 'NO_PENDING_ESTIMATE'
            ], 409);
        }

        if (! $serviceOrder->isEscrowHolding()) {
            return response()->json([
                'success' => false,
                'message' => 'Los fondos de esta orden ya no están retenidos en garantía.',
                'code' => 'ESCROW_NOT_HOLDING'
            ], 409);
        }

        try {
            if (! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_IN_PROGRESS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no puede continuar desde su estado actual.',
                    'code' => 'INVALID_TRANSITION'
                ], 409);
            }

            $serviceOrder->status = ServiceOrder::STATUS_IN_PROGRESS;
            $this->escrow->applyApprovedEstimate($serviceOrder);

            if ($serviceOrder->payment_mode !== null) {
                $serviceOrder->deposit_amount = $serviceOrder->payment_mode === 'FULL'
                    ? (float) $serviceOrder->total_amount
                    : round((float) $serviceOrder->total_amount * (float) config('bookings.deposit_percent', 50) / 100, 2);
                $serviceOrder->save();
            }
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo aprobar el estimado de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo aprobar el costo adicional. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Costo adicional aprobado. El profesional continúa con el trabajo.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * El cliente confirma la conformidad: el escrow se libera, se registra
     * la comisión de PideAca y el neto se acredita al balance del prestador.
     */
    public function confirmRelease(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ((int) $serviceOrder->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
                'code' => 'ORDER_NOT_FOUND'
            ], 404);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Primero el profesional debe marcar el trabajo como completado.',
                'code' => 'ORDER_NOT_COMPLETED',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if (! $serviceOrder->isEscrowHolding()) {
            return response()->json([
                'success' => false,
                'message' => 'Los fondos de esta orden ya no están retenidos en garantía.',
                'code' => 'ESCROW_NOT_HOLDING',
                'escrow_status' => $serviceOrder->escrow_status,
            ], 409);
        }

        $percent = (float) config('financials.pideaca_commission_percent', 10);

        try {
            DB::transaction(function () use ($serviceOrder, $percent) {
                $total = round((float) $serviceOrder->total_amount, 2);
                $commission = round($total * $percent / 100, 2);
                $net = round($total - $commission, 2);

                $serviceOrder->commission_amount = $commission;
                $serviceOrder->save();

                $this->escrow->release($serviceOrder);

                $provider = $serviceOrder->merchant;
                $provider->balance = round((float) $provider->balance + $net, 2);
                $provider->save();
            });

            $order = $serviceOrder->fresh();
            $order->load('merchant');

            Log::info('Móvil: escrow liberado de la orden #'.$order->id.' total $'
                .number_format((float) $order->total_amount, 2, ',', '.')
                .' comisión '.$percent.'% ($'.number_format((float) $order->commission_amount, 2, ',', '.')
                .') al balance del comercio (gateway manual).');
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo liberar el pago de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo liberar el pago. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago liberado. La comisión de PideAca se aplicó y el neto se acreditó al prestador.',
            'data' => [
                'order' => $this->orderPayload($order),
                'release' => [
                    'gross' => $order->total_amount,
                    'commission_percent' => $percent,
                    'commission_amount' => $order->commission_amount,
                    'net_amount' => number_format((float) $order->total_amount - (float) $order->commission_amount, 2, '.', ''),
                    'provider_balance' => $order->merchant->balance,
                ],
            ],
        ]);
    }

    /**
     * Valida con las reglas del Módulo B y devuelve 422 en español cuando
     * algo no cierra (igual que el catálogo móvil).
     *
     * @return array<string, mixed>|JsonResponse
     */
    private function validated(Request $request, array $rules)
    {
        try {
            return $this->serviceOrderData($request, $rules);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Revisá los datos de la solicitud.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Foto y audio de la urgencia en public/images/servicios
     * (igual que la web).
     */
    private function attachEmergencyMedia(ServiceOrder $order, Request $request): void
    {
        $destDir = public_path('images/servicios');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $paths = [];

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'urgencia_'.$order->id.'_foto.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);
            $paths['photo_path'] = $filename;
        }

        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $filename = 'urgencia_'.$order->id.'_audio.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);
            $paths['audio_path'] = $filename;
        }

        if ($paths !== []) {
            $order->update($paths);
        }
    }

    /**
     * Fotos y video del presupuesto en public/images/servicios
     * (igual que la web).
     *
     * @return array<int, array<string, string>>
     */
    private function storeQuoteMedia(ServiceOrder $order, Request $request): array
    {
        $destDir = public_path('images/servicios');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $attachments = [];

        foreach ((array) $request->file('photos', []) as $i => $file) {
            if (! $file) {
                continue;
            }

            $filename = 'quote_'.$order->id.'_foto_'.$i.'.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);
            $attachments[] = ['type' => 'photo', 'path' => $filename];
        }

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = 'quote_'.$order->id.'_video.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);
            $attachments[] = ['type' => 'video', 'path' => $filename];
        }

        return $attachments;
    }

    /**
     * Dirección legible de la cita a partir de los campos estructurados
     * (igual que la web).
     */
    private function composeAddress(array $data): ?string
    {
        $parts = array_filter([
            trim((string) ($data['address_street'] ?? '')) ?: null,
            trim((string) ($data['address_unit'] ?? '')) ?: null,
            trim((string) ($data['address_neighborhood'] ?? '')) ?: null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}

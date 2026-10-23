<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithServiceOrders;
use App\Models\ServiceOrder;
use App\Models\ServiceRating;
use App\Models\ServiceType;
use App\Services\ServiceEscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ServiceOrderController extends Controller
{
    use InteractsWithServiceOrders;

    public function __construct(private readonly ServiceEscrowService $escrow) {}

    /**
     * GET /api/v1/provider/service-orders
     *
     * Listado de ordenes de servicio del comercio logueado: alimenta la
     * pagina "Ordenes de Servicio" del prestador. Filtrable por estado y
     * tipo, paginado y siempre acotado al propio comercio.
     */
    public function indexForProvider(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_merge(ServiceOrder::STATUSES, ServiceOrder::DISPLAY_STATUSES))],
            'order_type' => ['nullable', 'string', 'in:'.implode(',', ServiceOrder::ORDER_TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = ServiceOrder::query()
            ->forProvider($provider)
            ->with(['subGroup', 'customer', 'quotes.provider', 'ratings'])
            ->when($data['status'] ?? null, fn ($query, $status) => $query->whereIn('status', ServiceOrder::legacyStatusesFor($status)))
            ->when($data['order_type'] ?? null, fn ($query, $type) => $query->ofType($type))
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 20))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                ],
                'statuses' => ServiceOrder::displayStatuses(),
                'order_types' => ServiceOrder::ORDER_TYPES,
                'orders' => collect($orders->items())->map(fn (ServiceOrder $order) => $this->orderPayload($order)),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/provider/service-orders/{serviceOrder}
     *
     * Detalle operativo de una orden para el prestador: datos de contacto y
     * ubicacion, adjuntos del cliente, desglose del presupuesto y linea de
     * tiempo. Se puede ver la orden asignada al comercio o la que sigue en
     * el pool de sus subgrupos.
     */
    public function showForProvider(ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $isMine = (int) $serviceOrder->merchant_id === (int) $provider->id;
        $isPool = $serviceOrder->merchant_id === null;

        if (! $isMine && ! $isPool) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
            ], 404);
        }

        if ($isPool && ! $provider->subgroups()->where('sub_groups.id', $serviceOrder->sub_group_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu comercio no ofrece este servicio.',
            ], 403);
        }

        $serviceOrder->loadMissing(['subGroup', 'customer', 'merchant.user', 'quotes.provider', 'ratings']);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $this->orderPayload($serviceOrder),
            ],
        ]);
    }

    /**
     * GET /api/v1/customer/service-orders
     *
     * Listado de las ordenes de servicio del cliente logueado: alimenta la
     * pagina "Mis Servicios". Filtrable por estado y tipo, paginado y siempre
     * acotado al propio usuario.
     */
    public function indexForCustomer(Request $request): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_merge(ServiceOrder::STATUSES, ServiceOrder::DISPLAY_STATUSES))],
            'order_type' => ['nullable', 'string', 'in:'.implode(',', ServiceOrder::ORDER_TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = ServiceOrder::query()
            ->where('customer_id', $customer->id)
            ->with(['subGroup', 'merchant', 'quotes.provider', 'ratings'])
            ->when($data['status'] ?? null, fn ($query, $status) => $query->whereIn('status', ServiceOrder::legacyStatusesFor($status)))
            ->when($data['order_type'] ?? null, fn ($query, $type) => $query->ofType($type))
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 20))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                ],
                'statuses' => ServiceOrder::displayStatuses(),
                'order_types' => ServiceOrder::ORDER_TYPES,
                'orders' => collect($orders->items())->map(fn (ServiceOrder $order) => $this->orderPayload($order)),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/customer/service-orders/{serviceOrder}
     *
     * Estado de una orden propia: alimenta el seguimiento en vivo de la
     * ventana de urgencias (el cliente la consulta mientras espera a que le
     * acepten el pedido y despues durante el camino del prestador).
     */
    public function showForCustomer(ServiceOrder $serviceOrder): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        if ((int) $serviceOrder->customer_id !== (int) $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés acceso a esta solicitud.',
            ], 403);
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
     * POST /api/v1/customer/service-orders/emergency
     *
     * Alta de urgencia 24/7: el cliente elige un prestador de las tarjetas
     * (flujo directo: la orden se le asigna a el) o un SubGrupo del pool, el
     * GPS y la foto/audio del problema. La orden nace en REQUESTED; el
     * prestador elegido (o los del subgrupo) la aceptan y se despachan.
     */
    public function requestEmergencyService(Request $request): JsonResponse
    {
        $customer = $this->currentCustomer();

        if ($customer instanceof JsonResponse) {
            return $customer;
        }

        // La urgencia no resuelve ubicacion por su cuenta: la ventana solo
        // manda direccion y coordenadas si el cliente las informo (campo de
        // direccion o boton "Usar mi ubicacion actual"); nada se deriva de la
        // direccion del perfil.
        $data = $this->serviceOrderData($request, [
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

        $target = $this->resolveServiceOrderTarget($data, ServiceType::CODE_EMERGENCY);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $merchantId = $target['provider_id'];
        $data['sub_group_id'] = $target['sub_group_id'];

        try {
            $order = DB::transaction(function () use ($data, $customer, $request, $merchantId) {
                $orden = ServiceOrder::create([
                    'customer_id' => $customer->id,
                    'sub_group_id' => $data['sub_group_id'],
                    'merchant_id' => $merchantId,
                    'order_type' => ServiceOrder::TYPE_EMERGENCY,
                    'status' => ServiceOrder::STATUS_REQUESTED,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'escrow_status' => ServiceOrder::ESCROW_HOLDING,
                    'description' => $data['description'] ?? null,
                    'address' => $data['address'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                ]);

                $this->attachMedia($orden, $request);

                return $orden;
            });
        } catch (Throwable $e) {
            Log::error('Urgencia: no se pudo crear la orden de servicio: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la solicitud de urgencia. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $merchantId !== null
                ? 'Urgencia enviada al prestador elegido. Te avisa cuando la acepte.'
                : 'Solicitud de urgencia enviada. Estamos avisando a los profesionales de guardia.',
            'data' => [
                'order' => $this->orderPayload($order->fresh()),
            ],
        ], 201);
    }

    /**
     * POST /api/v1/provider/service-orders/{serviceOrder}/accept
     *
     * El prestador toma una orden del pool de su subgrupo (urgencias y citas):
     * la orden queda ligada a su comercio, pasa a PROVIDER_DISPATCHED y se
     * devuelven los datos para el tracking en vivo.
     */
    public function acceptEmergencyRequest(ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_EMERGENCY, ServiceOrder::TYPE_SCHEDULED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no se toma con una aceptacion directa.',
            ], 409);
        }

        if ($serviceOrder->merchant_id !== null && (int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue tomada por otro profesional.',
            ], 409);
        }

        if ($serviceOrder->order_type === ServiceOrder::TYPE_SCHEDULED
            && $serviceOrder->scheduled_at
            && ServiceOrder::where('merchant_id', $provider->id)
                ->whereKeyNot($serviceOrder->id)
                ->where('status', '!=', ServiceOrder::STATUS_CANCELLED)
                ->where('scheduled_at', $serviceOrder->scheduled_at)
                ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tenes una cita en ese horario.',
            ], 409);
        }

        if (! $provider->subgroups()->where('sub_groups.id', $serviceOrder->sub_group_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu comercio no ofrece este servicio.',
            ], 403);
        }

        // La urgencia se despacha al instante; la cita recien se confirma
        // (el tecnico va a cargar el estimado desde ACCEPTED).
        $estadoAceptado = $serviceOrder->order_type === ServiceOrder::TYPE_SCHEDULED
            ? ServiceOrder::STATUS_ACCEPTED
            : ServiceOrder::STATUS_PROVIDER_DISPATCHED;

        try {
            DB::transaction(function () use ($serviceOrder, $provider, $estadoAceptado) {
                $actual = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->first();

                // Pool: merchant null. Directo: merchant ya es este provider.
                if (($actual->merchant_id !== null
                    && (int) $actual->merchant_id !== (int) $provider->id)
                    || $actual->status !== ServiceOrder::STATUS_REQUESTED
                    || ! $actual->canTransitionTo($estadoAceptado)) {
                    throw new RuntimeException('La solicitud ya no esta disponible.');
                }

                $actual->merchant_id = $provider->id;
                $actual->status = $estadoAceptado;
                $actual->accepted_at = now();
                // La urgencia se despacha en el acto: ya esta "en camino".
                $actual->started_at = $estadoAceptado === ServiceOrder::STATUS_PROVIDER_DISPATCHED
                    ? now()
                    : $actual->started_at;
                $actual->save();
            });
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue tomada por otro profesional o ya no esta disponible.',
                'status' => $serviceOrder->fresh()->status,
            ], 409);
        } catch (Throwable $e) {
            Log::error('Urgencia: no se pudo aceptar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo aceptar la solicitud. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $serviceOrder->order_type === ServiceOrder::TYPE_SCHEDULED
                ? 'Cita aceptada. El cliente ya ve tu comercio confirmado.'
                : 'Solicitud aceptada. El cliente ya puede seguirte en vivo.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
                'tracking' => $this->trackingPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/provider/service-orders/{serviceOrder}/dispatch
     *
     * El prestador marca que salio a atender el trabajo (en camino). Aplica a
     * las citas ya aceptadas y a los presupuestos que el cliente confirmo: la
     * orden pasa a PROVIDER_DISPATCHED y queda registrada la hora de inicio.
     */
    public function dispatchService(ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($serviceOrder->merchant_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
            ], 404);
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_SCHEDULED, ServiceOrder::TYPE_QUOTE], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo las citas y los presupuestos confirmados se pueden marcar como en camino.',
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_ACCEPTED) {
            return response()->json([
                'success' => false,
                'message' => 'La cita no esta lista para iniciar el trabajo.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        try {
            DB::transaction(function () use ($serviceOrder) {
                $actual = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->first();

                if (! $actual || $actual->status !== ServiceOrder::STATUS_ACCEPTED
                    || ! $actual->canTransitionTo(ServiceOrder::STATUS_PROVIDER_DISPATCHED)) {
                    throw new RuntimeException('La orden ya no esta disponible.');
                }

                $actual->status = ServiceOrder::STATUS_PROVIDER_DISPATCHED;
                $actual->started_at = now();
                $actual->save();
            });
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La cita ya no se puede iniciar desde su estado actual.',
                'status' => $serviceOrder->fresh()->status,
            ], 409);
        } catch (Throwable $e) {
            Log::error('Cita: no se pudo iniciar el trabajo de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar el trabajo. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trabajo iniciado. El cliente ya puede verte en camino.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/provider/service-orders/{serviceOrder}/onsite-estimate
     *
     * El tecnico en el lugar (o el profesional que confirmo la cita) carga
     * el precio cerrado del trabajo. La orden pasa a QUOTED: los fondos
     * siguen en HOLDING y el cliente debe aprobarlo antes de continuar.
     * Si el presupuesto ya fue enviado y esta esperando la aceptacion del
     * cliente, el mismo endpoint lo actualiza.
     */
    public function addOnsiteEstimate(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_EMERGENCY, ServiceOrder::TYPE_SCHEDULED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no usa estimado en el lugar.',
            ], 409);
        }

        if ($serviceOrder->merchant_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
            ], 404);
        }

        if (! in_array($serviceOrder->status, [
            ServiceOrder::STATUS_PROVIDER_DISPATCHED,
            ServiceOrder::STATUS_ACCEPTED,
            ServiceOrder::STATUS_QUOTED,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'El estimado solo puede cargarse cuando el trabajo esta activo.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'additional_cost' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            // QUOTED -> QUOTED no es una transicion valida: en ese caso el
            // presupuesto ya fue enviado y lo que se hace es actualizarlo.
            if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED
                && ! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_QUOTED)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no puede pasar a presupuesto desde su estado actual.',
                ], 409);
            }

            $serviceOrder->additional_cost = $data['additional_cost'];
            $serviceOrder->estimate_details = $data['details'] ?? $serviceOrder->estimate_details;
            $serviceOrder->status = ServiceOrder::STATUS_QUOTED;
            $serviceOrder->save();
        } catch (Throwable $e) {
            Log::error('Urgencia: no se pudo cargar el estimado de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el costo adicional. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estimado enviado. El cliente debe aprobarlo para continuar.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/customer/service-orders/{serviceOrder}/approve-onsite-estimate
     *
     * El cliente autoriza el precio cargado con 1-click: la orden pasa a
     * IN_PROGRESS y el importe retenido en escrow queda en el estimado
     * aprobado.
     */
    public function approveOnsiteEstimate(ServiceOrder $serviceOrder): JsonResponse
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

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_EMERGENCY, ServiceOrder::TYPE_SCHEDULED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no usa estimado en el lugar.',
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un estimado pendiente de aprobacion en esta orden.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if ((float) $serviceOrder->additional_cost <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no tiene costos adicionales pendientes.',
            ], 409);
        }

        if (! $serviceOrder->isEscrowHolding()) {
            return response()->json([
                'success' => false,
                'message' => 'Los fondos de esta orden ya no estan retenidos en garantia.',
            ], 409);
        }

        try {
            if (! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_IN_PROGRESS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no puede continuar desde su estado actual.',
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
            Log::error('Urgencia: no se pudo aprobar el estimado de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo aprobar el costo adicional. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Costo adicional aprobado. El profesional continua con el trabajo.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/provider/service-orders/{serviceOrder}/complete
     *
     * El prestador marca el trabajo como terminado adjuntando una foto
     * comprobante del resultado: la orden pasa a COMPLETED con fecha de
     * cierre y la foto queda registrada en completion_photo_path. Los fondos
     * siguen en escrow hasta que el cliente confirme la conformidad.
     */
    public function completeService(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($serviceOrder->merchant_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
            ], 404);
        }

        if ($serviceOrder->status === ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'El trabajo tiene una cotizacion pendiente de aprobacion antes de poder completarlo.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if (! in_array($serviceOrder->status, [
            ServiceOrder::STATUS_ACCEPTED,
            ServiceOrder::STATUS_PROVIDER_DISPATCHED,
            ServiceOrder::STATUS_IN_PROGRESS,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Este trabajo aun no puede marcarse como completado.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if (! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_COMPLETED)) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no puede completarse desde su estado actual.',
            ], 409);
        }

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $destDir = public_path('images/servicios');

            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $file = $request->file('photo');
            $filename = 'completada_'.$serviceOrder->id.'_foto.'.strtolower($file->getClientOriginalExtension());
            $file->move($destDir, $filename);

            DB::transaction(function () use ($serviceOrder, $filename) {
                $serviceOrder->status = ServiceOrder::STATUS_COMPLETED;
                $serviceOrder->completed_at = now();
                $serviceOrder->completion_photo_path = $filename;
                $serviceOrder->save();
            });
        } catch (Throwable $e) {
            Log::error('Cierre: no se pudo completar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo marcar el trabajo como completado. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trabajo marcado como completado. El cliente debe confirmar la conformidad para liberar el pago.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * POST /api/v1/customer/service-orders/{serviceOrder}/confirm-release
     *
     * El cliente confirma la conformidad del trabajo: el escrow pasa a
     * RELEASED, se registra la comision de PideAca sobre el total liberado
     * y el neto se acredita en el balance del prestador.
     */
    public function confirmAndReleasePayment(ServiceOrder $serviceOrder): JsonResponse
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

        if ($serviceOrder->status !== ServiceOrder::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Primero el profesional debe marcar el trabajo como completado.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if (! $serviceOrder->isEscrowHolding()) {
            return response()->json([
                'success' => false,
                'message' => 'Los fondos de esta orden ya no estan retenidos en garantia.',
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

            Log::info('Escrow: liberacion de la orden #'.$order->id.' total $'
                .number_format((float) $order->total_amount, 2, ',', '.')
                .' comision '.$percent.'% ($'.number_format((float) $order->commission_amount, 2, ',', '.')
                .') al balance del comercio (gateway manual).');
        } catch (Throwable $e) {
            Log::error('Cierre: no se pudo liberar el pago de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo liberar el pago. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago liberado. La comision de PideAca se aplico y el neto se acredito al prestador.',
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
     * POST /api/v1/customer/service-orders/{serviceOrder}/rate
     * POST /api/v1/provider/service-orders/{serviceOrder}/rate
     *
     * Calificacion bilateral 1-5 estrellas con comentario opcional: el
     * cliente valora al prestador (y se recalcula su rating publico) y el
     * prestador califica al cliente. Una sola calificacion por autor y orden,
     * y solo sobre trabajos completados.
     */
    public function rateService(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        $user = Auth::user();
        $esCliente = (int) $serviceOrder->customer_id === (int) $user->id;
        $provider = $user->provider;
        $esPrestador = $provider && (int) $serviceOrder->merchant_id === (int) $provider->id;

        if (! $esCliente && ! $esPrestador) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu usuario.',
            ], 404);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden calificar trabajos completados.',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $rateeId = $esCliente ? $serviceOrder->merchant->user_id : $serviceOrder->customer_id;

        if (ServiceRating::where('service_order_id', $serviceOrder->id)
            ->where('reviewer_id', $user->id)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya calificaste este trabajo.',
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

            if ($esCliente) {
                $promedio = ServiceRating::where('ratee_id', $rateeId)->avg('stars');
                $serviceOrder->merchant->rating = round((float) $promedio, 2);
                $serviceOrder->merchant->save();
                $providerRating = $serviceOrder->merchant->rating;
            }
        } catch (Throwable $e) {
            Log::error('Calificacion: no se pudo calificar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la calificacion. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $esCliente
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
     * POST /api/v1/customer/service-orders/{serviceOrder}/cancel
     *
     * El cliente cancela su propia orden mientras seaCancelable (antes de que
     * el trabajo empiece): la orden pasa a CANCELLED y cualquier fondo en
     * HOLDING se devuelve al cliente (escrow REFUNDED).
     */
    public function cancelService(ServiceOrder $serviceOrder): JsonResponse
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

        if (! $serviceOrder->isCancellable()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya no se puede cancelar.',
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
            Log::error('Cancelacion: no se pudo cancelar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cancelar la orden. Intenta nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada. Los fondos retenidos se devuelven al cliente.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * Guarda la foto y el audio del problema en public/images/servicios.
     */
    private function attachMedia(ServiceOrder $order, Request $request): void
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

    private function trackingPayload(ServiceOrder $order): array
    {
        $order->loadMissing(['merchant', 'customer']);

        return [
            'order_id' => $order->id,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'accepted_at' => $order->updated_at?->toIso8601String(),
            'eta_minutes' => null,
            'provider' => [
                'id' => $order->merchant->id,
                'business_name' => $order->merchant->business_name,
                'phone' => $order->merchant->phone,
                'whatsapp' => $order->merchant->whatsapp,
                'zone' => $order->merchant->zone,
            ],
            'problem' => [
                'description' => $order->description,
                'photo_url' => $order->photo_path ? asset('images/servicios/'.$order->photo_path) : null,
                'audio_url' => $order->audio_path ? asset('images/servicios/'.$order->audio_path) : null,
            ],
            'location' => [
                'latitude' => $order->latitude,
                'longitude' => $order->longitude,
            ],
        ];
    }
}

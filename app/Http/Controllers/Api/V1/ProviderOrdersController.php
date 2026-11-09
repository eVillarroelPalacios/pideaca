<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\InteractsWithServiceOrders;
use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Panel del prestador en la app móvil.
 *
 * Espeja el flujo web de "Órdenes de Servicio" sin tocarlo: pool de
 * solicitudes de sus rubros, aceptar, ponerse en camino, estimar,
 * proponer presupuestos, completar con foto y calificar al cliente.
 * Todo exige un comercio asociado al usuario (403 si no lo tiene).
 */
class ProviderOrdersController extends Controller
{
    use InteractsWithServiceOrders;

    /**
     * Pool de mis rubros + mis órdenes asignadas, paginado.
     */
    public function index(Request $request)
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_merge(ServiceOrder::STATUSES, ServiceOrder::DISPLAY_STATUSES))],
            'order_type' => ['nullable', 'string', 'in:'.implode(',', ServiceOrder::ORDER_TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'mine' => ['sometimes', 'boolean'],
        ]);

        $orders = ServiceOrder::query()
            ->forProvider($provider)
            ->with(['subGroup', 'customer', 'quotes.provider', 'ratings'])
            ->when($request->boolean('mine'), fn ($query) => $query->where('merchant_id', $provider->id))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->whereIn('status', ServiceOrder::legacyStatusesFor($status)))
            ->when($data['order_type'] ?? null, fn ($query, $type) => $query->ofType($type))
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                    'balance' => $provider->balance !== null ? (float) $provider->balance : null,
                ],
                'orders' => collect($orders->items())
                    ->map(fn (ServiceOrder $order) => $this->orderPayload($order))
                    ->values()
                    ->all(),
            ],
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

    /**
     * Detalle operativo: orden propia o del pool de mis rubros.
     */
    public function show(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $visibility = $this->checkVisibility($provider, $serviceOrder);

        if ($visibility instanceof JsonResponse) {
            return $visibility;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $this->orderPayload($serviceOrder),
            ],
        ]);
    }

    /**
     * Tomar una urgencia o cita del pool (o confirmar una directa).
     * La urgencia se despacha al instante; la cita queda aceptada.
     */
    public function accept(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_EMERGENCY, ServiceOrder::TYPE_SCHEDULED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no se toma con una aceptación directa.',
                'code' => 'NOT_ACCEPTABLE',
            ], 409);
        }

        if ($serviceOrder->merchant_id !== null && (int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue tomada por otro profesional.',
                'code' => 'ALREADY_TAKEN',
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
                'message' => 'Ya tenés una cita en ese horario.',
                'code' => 'SCHEDULE_CONFLICT',
            ], 409);
        }

        if (! $provider->subgroups()->where('sub_groups.id', $serviceOrder->sub_group_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu comercio no ofrece este servicio.',
                'code' => 'NOT_IN_SUBGROUP',
            ], 403);
        }

        $acceptedStatus = $serviceOrder->order_type === ServiceOrder::TYPE_SCHEDULED
            ? ServiceOrder::STATUS_ACCEPTED
            : ServiceOrder::STATUS_PROVIDER_DISPATCHED;

        try {
            DB::transaction(function () use ($serviceOrder, $provider, $acceptedStatus) {
                $actual = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->first();

                if (($actual->merchant_id !== null
                    && (int) $actual->merchant_id !== (int) $provider->id)
                    || $actual->status !== ServiceOrder::STATUS_REQUESTED
                    || ! $actual->canTransitionTo($acceptedStatus)) {
                    throw new RuntimeException('La solicitud ya no está disponible.');
                }

                $actual->merchant_id = $provider->id;
                $actual->status = $acceptedStatus;
                $actual->accepted_at = now();
                $actual->started_at = $acceptedStatus === ServiceOrder::STATUS_PROVIDER_DISPATCHED
                    ? now()
                    : $actual->started_at;
                $actual->save();
            });
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue tomada por otro profesional o ya no está disponible.',
                'code' => 'ALREADY_TAKEN',
                'status' => $serviceOrder->fresh()->status,
            ], 409);
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo aceptar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo aceptar la solicitud. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $serviceOrder->order_type === ServiceOrder::TYPE_SCHEDULED
                ? 'Cita aceptada. El cliente ya ve tu comercio confirmado.'
                : 'Solicitud aceptada. El cliente ya puede seguirte en vivo.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ]);
    }

    /**
     * Marcar que salí a atender el trabajo (citas y presupuestos
     * confirmados en ACCEPTED pasan a PROVIDER_DISPATCHED).
     */
    public function dispatch(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ((int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
                'code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_SCHEDULED, ServiceOrder::TYPE_QUOTE], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo las citas y los presupuestos confirmados se pueden marcar como en camino.',
                'code' => 'NOT_DISPATCHABLE'
            ], 409);
        }

        if ($serviceOrder->status !== ServiceOrder::STATUS_ACCEPTED) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no está lista para iniciar el trabajo.',
                'code' => 'INVALID_STATUS',
                'status' => $serviceOrder->status,
            ], 409);
        }

        try {
            DB::transaction(function () use ($serviceOrder) {
                $actual = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->first();

                if (! $actual || $actual->status !== ServiceOrder::STATUS_ACCEPTED
                    || ! $actual->canTransitionTo(ServiceOrder::STATUS_PROVIDER_DISPATCHED)) {
                    throw new RuntimeException('La orden ya no está disponible.');
                }

                $actual->status = ServiceOrder::STATUS_PROVIDER_DISPATCHED;
                $actual->started_at = now();
                $actual->save();
            });
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La orden ya no se puede iniciar desde su estado actual.',
                'code' => 'INVALID_STATUS',
                'status' => $serviceOrder->fresh()->status,
            ], 409);
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo iniciar el trabajo de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar el trabajo. Intentá nuevamente.',
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
     * Cargar el precio cerrado del trabajo en el lugar (o actualizarlo).
     * La orden pasa a QUOTED hasta que el cliente lo apruebe.
     */
    public function estimate(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if (! in_array($serviceOrder->order_type, [ServiceOrder::TYPE_EMERGENCY, ServiceOrder::TYPE_SCHEDULED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no usa estimado en el lugar.',
                'code' => 'NO_ESTIMATE_FLOW'
            ], 409);
        }

        if ((int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
                'code' => 'ORDER_NOT_FOUND'
            ], 404);
        }

        if (! in_array($serviceOrder->status, [
            ServiceOrder::STATUS_PROVIDER_DISPATCHED,
            ServiceOrder::STATUS_ACCEPTED,
            ServiceOrder::STATUS_QUOTED,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'El estimado solo puede cargarse cuando el trabajo está activo.',
                'code' => 'INVALID_STATUS',
                'status' => $serviceOrder->status,
            ], 409);
        }

        $data = $request->validate([
            'additional_cost' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            if ($serviceOrder->status !== ServiceOrder::STATUS_QUOTED
                && ! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_QUOTED)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no puede pasar a presupuesto desde su estado actual.',
                    'code' => 'INVALID_TRANSITION'
                ], 409);
            }

            $serviceOrder->additional_cost = $data['additional_cost'];
            $serviceOrder->estimate_details = $data['details'] ?? $serviceOrder->estimate_details;
            $serviceOrder->status = ServiceOrder::STATUS_QUOTED;
            $serviceOrder->save();
        } catch (Throwable $e) {
            Log::error('Móvil: no se pudo cargar el estimado de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el costo adicional. Intentá nuevamente.',
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
     * Enviar (o actualizar) mi propuesta en una solicitud de presupuesto
     * del pool o asignada. La primera propuesta pasa la orden a QUOTED.
     */
    public function propose(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($serviceOrder->order_type !== ServiceOrder::TYPE_QUOTE) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no pertenece al flujo de presupuestos.',
                'code' => 'NOT_A_QUOTE'
            ], 409);
        }

        if ($serviceOrder->merchant_id !== null && (int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue tomada por otro profesional.',
                'code' => 'ALREADY_TAKEN'
            ], 409);
        }

        if (! $provider->subgroups()->where('sub_groups.id', $serviceOrder->sub_group_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu comercio no ofrece este servicio.',
                'code' => 'NOT_IN_SUBGROUP'
            ], 403);
        }

        if (! in_array($serviceOrder->status, [ServiceOrder::STATUS_REQUESTED, ServiceOrder::STATUS_QUOTED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud ya no acepta propuestas.',
                'code' => 'INVALID_STATUS',
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
            Log::error('Móvil: no se pudo enviar la propuesta de la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar la propuesta. Intentá nuevamente.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propuesta enviada. El cliente debe aceptarla para confirmar el trabajo.',
            'data' => [
                'order' => $this->orderPayload($serviceOrder->fresh()),
            ],
        ], 201);
    }

    /**
     * Marcar el trabajo como terminado con foto comprobante.
     * Los fondos siguen en escrow hasta que el cliente confirme.
     */
    public function complete(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $provider = $this->ownProvider($request);

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ((int) $serviceOrder->merchant_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
                'code' => 'ORDER_NOT_FOUND'
            ], 404);
        }

        if ($serviceOrder->status === ServiceOrder::STATUS_QUOTED) {
            return response()->json([
                'success' => false,
                'message' => 'El trabajo tiene una cotización pendiente de aprobación antes de poder completarlo.',
                'code' => 'PENDING_QUOTE',
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
                'message' => 'Este trabajo aún no puede marcarse como completado.',
                'code' => 'INVALID_STATUS',
                'status' => $serviceOrder->status,
            ], 409);
        }

        if (! $serviceOrder->canTransitionTo(ServiceOrder::STATUS_COMPLETED)) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no puede completarse desde su estado actual.',
                'code' => 'INVALID_TRANSITION'
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
            Log::error('Móvil: no se pudo completar la orden #'.$serviceOrder->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo marcar el trabajo como completado. Intentá nuevamente.',
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
     * El usuario logueado debe tener un comercio asociado.
     *
     * @return Provider|JsonResponse
     */
    private function ownProvider(Request $request)
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
                'code' => 'NOT_A_PROVIDER',
            ], 403);
        }

        return $provider;
    }

    /**
     * Orden propia o del pool de los rubros que ofrece el comercio.
     *
     * @return null|JsonResponse
     */
    private function checkVisibility(Provider $provider, ServiceOrder $serviceOrder)
    {
        $isMine = (int) $serviceOrder->merchant_id === (int) $provider->id;
        $isPool = $serviceOrder->merchant_id === null;

        if (! $isMine && ! $isPool) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no pertenece a tu comercio.',
                'code' => 'ORDER_NOT_FOUND'
            ], 404);
        }

        if ($isPool && ! $provider->subgroups()->where('sub_groups.id', $serviceOrder->sub_group_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu comercio no ofrece este servicio.',
                'code' => 'NOT_IN_SUBGROUP'
            ], 403);
        }

        return null;
    }
}

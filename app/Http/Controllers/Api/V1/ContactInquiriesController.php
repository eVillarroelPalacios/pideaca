<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Consultas y sugerencias del formulario de contacto, para la app movil.
 *
 * Espeja la seccion "consultas-sugerencias" del panel web sin tocarla:
 * misma fuente (modelo ContactInquiry), mismos estados y mismo permiso
 * (pagina consultas-sugerencias). Agrega paginacion y conteos por estado
 * para que la app pueda mostrar los filtros sin traer todo el historial.
 */
class ContactInquiriesController extends Controller
{
    public const STATUSES = ['new', 'in_progress', 'resolved'];

    /**
     * Listado paginado, opcionalmente filtrado por estado.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $request->validate([
            'status' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $status = (string) ($data['status'] ?? '');

        $inquiries = ContactInquiry::query()
            // Igual que la web: un estado desconocido no filtra, muestra todo.
            ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($data['per_page'] ?? 20));

        $grouped = ContactInquiry::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => collect($inquiries->items())
                ->map(fn (ContactInquiry $inquiry) => $this->payload($inquiry))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
            'counts' => [
                'all' => (int) $grouped->sum(),
                'new' => (int) ($grouped['new'] ?? 0),
                'in_progress' => (int) ($grouped['in_progress'] ?? 0),
                'resolved' => (int) ($grouped['resolved'] ?? 0),
            ],
            'filters' => [
                'statuses' => self::STATUSES,
            ],
        ]);
    }

    /**
     * Detalle de una consulta.
     */
    public function show(Request $request, ContactInquiry $contactInquiry): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'contact_inquiry' => $this->payload($contactInquiry),
                'statuses' => self::STATUSES,
            ],
        ]);
    }

    /**
     * Cambia el estado de la consulta (Nueva / En proceso / Resuelta).
     */
    public function updateStatus(Request $request, ContactInquiry $contactInquiry): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ], [
            'status.required' => 'Seleccioná un estado.',
            'status.in' => 'Seleccioná un estado válido.',
        ]);

        $contactInquiry->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'contact_inquiry' => $this->payload($contactInquiry->fresh()),
        ]);
    }

    /**
     * Permiso identico al guard web `page:consultas-sugerencias`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('consultas-sugerencias')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * Forma publica de una consulta (fechas en ISO 8601).
     */
    private function payload(ContactInquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'full_name' => $inquiry->full_name,
            'email' => $inquiry->email,
            'inquiry_type' => $inquiry->inquiry_type,
            'message' => $inquiry->message,
            'status' => $inquiry->status,
            'created_at' => $inquiry->created_at?->toIso8601String(),
        ];
    }
}

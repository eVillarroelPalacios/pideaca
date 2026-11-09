<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Push tokens y centro de notificaciones de la app móvil.
 *
 * La app registra su ExponentPushToken al iniciar sesión (sirve para
 * Android y iOS) y lo borra al cerrar sesión. El centro lista lo que los
 * observers del Módulo B fueron generando (propuestas, aceptaciones,
 * calificaciones...).
 */
class PushController extends Controller
{
    public function storeToken(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios'])],
        ]);

        if (! PushToken::looksValid($data['token'])) {
            return response()->json([
                'success' => false,
                'message' => 'El token de notificación no es válido.',
                'code' => 'INVALID_PUSH_TOKEN',
            ], 422);
        }

        $user = $request->user();

        // Un mismo dispositivo (mismo token) puede cambiar de usuario.
        PushToken::where('token', $data['token'])->delete();

        $token = PushToken::create([
            'user_id' => $user->id,
            'token' => $data['token'],
            'platform' => $data['platform'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notificaciones activadas en este dispositivo.',
            'data' => [
                'id' => $token->id,
                'platform' => $token->platform,
            ],
        ], 201);
    }

    public function destroyToken(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        PushToken::where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificaciones desactivadas en este dispositivo.',
        ]);
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'unread_only' => ['sometimes', 'boolean'],
        ]);

        $query = $request->user()->userNotifications()->latest();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate((int) ($data['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => collect($notifications->items())
                ->map(fn (UserNotification $notification) => $this->payload($notification))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->userNotifications()->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification)
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'La notificación no pertenece a tu usuario.',
                'code' => 'NOTIFICATION_NOT_FOUND',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $this->payload($notification->fresh()),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $count = $request->user()->userNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $count > 0 ? 'Todas marcadas como leídas.' : 'No tenías sin leer.',
            'data' => ['marked' => $count],
        ]);
    }

    private function payload(UserNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}

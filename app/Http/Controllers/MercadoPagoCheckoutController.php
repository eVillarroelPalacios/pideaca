<?php

namespace App\Http\Controllers;

use App\Models\MpSellerAccount;
use App\Models\Order;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\MercadoPagoGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Checkout Mercado Pago (Fase 3): crea la preferencia y atiende el webhook.
 * Sirve a web y móvil (mismo controlador, distintas rutas).
 */
class MercadoPagoCheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function start(Request $request)
    {
        $data = $request->validate([
            'order_type' => ['required', 'string', 'in:order,service_order'],
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $owner = $data['order_type'] === 'service_order'
            ? ServiceOrder::whereKey((int) $data['order_id'])->value('customer_id')
            : Order::whereKey((int) $data['order_id'])->value('user_id');

        if (! $owner || ((int) $owner !== (int) $request->user()->id && ! $request->user()->isAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Ese pedido no existe o no es tuyo.',
            ], 404);
        }

        try {
            $result = $this->checkout->startMercadoPago(
                $data['order_type'],
                (int) $data['order_id'],
                route('checkout.mp.return'),
                route('checkout.mp.webhook')
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No pudimos iniciar el pago. Intentá de nuevo.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'init_point' => $result['init_point'],
            'preference_id' => $result['preference_id'],
            'charge_id' => $result['charge']->id,
            'split' => $result['split'] ?? false,
        ]);
    }

    public function webhook(Request $request)
    {
        try {
            $status = $this->checkout->handleWebhook(
                $request->all(),
                $request->header('x-signature'),
                $request->header('x-request-id')
            );
        } catch (ValidationException) {
            return response()->json(['success' => false], 403);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true, 'status' => $status]);
    }

    public function retorno(Request $request)
    {
        return view('checkout.mp-return', [
            'status' => $request->input('status', 'pending'),
            'chargeId' => $request->input('charge_id'),
        ]);
    }

    /**
     * Estado de la cuenta MP conectada del usuario autenticado.
     */
    public function sellerStatus(Request $request)
    {
        $account = MpSellerAccount::where('user_id', $request->user()->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'connected' => $account !== null,
                'mp_user_id' => $account?->mp_user_id,
            ],
        ]);
    }

    /**
     * URL de autorización OAuth (el front la abre en el navegador).
     */
    public function connectUrl(Request $request)
    {
        if (! MercadoPagoGateway::oauthConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Conexión con Mercado Pago no configurada.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ['url' => MercadoPagoGateway::authorizeUrl($this->connectState($request->user()->id))],
        ]);
    }

    /**
     * Redirige al OAuth de Mercado Pago (flujo web directo).
     */
    public function connect(Request $request)
    {
        if (! MercadoPagoGateway::oauthConfigured()) {
            abort(422, 'Conexión con Mercado Pago no configurada.');
        }

        return redirect()->away(MercadoPagoGateway::authorizeUrl($this->connectState($request->user()->id)));
    }

    /**
     * Callback de OAuth: canjea el code y guarda los tokens cifrados.
     * Sin sesión: el state firmado identifica al usuario (vale 15 min).
     */
    public function callback(Request $request)
    {
        $userId = $this->verifyConnectState((string) $request->input('state', ''));

        if (! $userId || ! ($user = User::find($userId))) {
            return response()->view('checkout.mp-connected', ['ok' => false, 'message' => 'Enlace inválido o vencido. Volvé a intentarlo.'], 422);
        }

        $code = (string) $request->input('code', '');

        if ($code === '') {
            return response()->view('checkout.mp-connected', ['ok' => false, 'message' => 'Mercado Pago no autorizó la conexión.'], 422);
        }

        $tokens = (new MercadoPagoGateway)->exchangeCode($code);

        if (! is_array($tokens) || empty($tokens['access_token']) || empty($tokens['user_id'])) {
            return response()->view('checkout.mp-connected', ['ok' => false, 'message' => 'No se pudo completar la conexión. Intentá de nuevo.'], 422);
        }

        MpSellerAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'mp_user_id' => (string) $tokens['user_id'],
                'access_token' => (string) $tokens['access_token'],
                'refresh_token' => isset($tokens['refresh_token']) ? (string) $tokens['refresh_token'] : null,
                'public_key' => $tokens['public_key'] ?? null,
                'live_mode' => ($tokens['live_mode'] ?? true) ? true : false,
                'expires_at' => isset($tokens['expires_in']) ? now()->addSeconds((int) $tokens['expires_in']) : null,
            ]
        );

        return response()->view('checkout.mp-connected', ['ok' => true, 'message' => null]);
    }

    /**
     * Desconecta la cuenta MP del usuario autenticado.
     */
    public function disconnect(Request $request)
    {
        MpSellerAccount::where('user_id', $request->user()->id)->delete();

        return response()->json(['success' => true, 'message' => 'Cuenta desvinculada.']);
    }

    /**
     * State firmado user_id:timestamp:random (HMAC con APP_KEY).
     */
    private function connectState(int $userId): string
    {
        $payload = $userId.':'.time().':'.bin2hex(random_bytes(8));
        $sig = hash_hmac('sha256', $payload, (string) config('app.key'));

        return rtrim(strtr(base64_encode($payload.':'.$sig), '+/', '-_'), '=');
    }

    private function verifyConnectState(string $state): ?int
    {
        $raw = base64_decode(strtr($state, '-_', '+/'), true);

        if (! is_string($raw)) {
            return null;
        }

        $parts = explode(':', $raw);

        if (count($parts) !== 4 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1])) {
            return null;
        }

        [$userId, $ts, $rand, $sig] = $parts;

        if (! hash_equals(hash_hmac('sha256', $userId.':'.$ts.':'.$rand, (string) config('app.key')), (string) $sig)) {
            return null;
        }

        if (time() - (int) $ts > 900) {
            return null;
        }

        return (int) $userId;
    }
}

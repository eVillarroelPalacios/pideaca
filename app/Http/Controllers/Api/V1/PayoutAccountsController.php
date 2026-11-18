<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayoutAccount;
use App\Services\PayoutAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Billeteras de cobro del usuario (móvil y panel web vía fetch).
 *
 * CRUD propio + default + activar/desactivar. La validación fuerte
 * (CBU/alias/IBAN) vive en PayoutAccountService.
 */
class PayoutAccountsController extends Controller
{
    public function __construct(private PayoutAccountService $wallets) {}

    private function payload(PayoutAccount $a): array
    {
        $a->loadMissing('walletType');

        return [
            'id' => $a->id,
            'currency' => $a->currency,
            'country' => $a->country,
            'wallet_type_id' => $a->wallet_type_id,
            'wallet_type' => $a->walletType ? $a->walletType->name : null,
            'label' => $a->label,
            'holder_name' => $a->holder_name,
            'cbu' => $a->cbu,
            'alias_cbu' => $a->alias_cbu,
            'account_ref' => $a->account_ref,
            'iban' => $a->iban,
            'bank' => $a->bank,
            'is_default' => (bool) $a->is_default,
            'is_active' => (bool) $a->is_active,
        ];
    }

    private function listPayload($user): array
    {
        return [
            'success' => true,
            'can_operate' => PayoutAccountService::canOperate($user),
            'data' => collect($this->wallets->list($user))->map(fn ($a) => $this->payload($a))->values()->all(),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->listPayload($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
            'country' => ['nullable', 'string', 'size:2'],
            'wallet_type_id' => ['nullable', 'integer', 'exists:wallet_types,id'],
            'label' => ['nullable', 'string', 'max:100'],
            'holder_name' => ['required', 'string', 'max:255'],
            'cbu' => ['nullable', 'string', 'max:22'],
            'alias_cbu' => ['nullable', 'string', 'max:100'],
            'account_ref' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'bank' => ['nullable', 'string', 'max:100'],
        ], [
            'holder_name.required' => 'Ingresá el titular de la cuenta.',
        ]);

        try {
            $account = $this->wallets->create($request->user(), $data);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billetera agregada correctamente.',
            'account' => $this->payload($account),
            'can_operate' => PayoutAccountService::canOperate($request->user()),
        ], 201);
    }

    public function update(Request $request, PayoutAccount $account): JsonResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'cbu' => ['nullable', 'string', 'max:22'],
            'alias_cbu' => ['nullable', 'string', 'max:100'],
            'account_ref' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'bank' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $account = $this->wallets->update($request->user(), $account, $data);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billetera actualizada correctamente.',
            'account' => $this->payload($account),
        ]);
    }

    public function setDefault(Request $request, PayoutAccount $account): JsonResponse
    {
        try {
            $account = $this->wallets->setDefault($request->user(), $account);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billetera predeterminada actualizada.',
            'account' => $this->payload($account),
        ]);
    }

    public function deactivate(Request $request, PayoutAccount $account): JsonResponse
    {
        try {
            $account = $this->wallets->deactivate($request->user(), $account);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billetera desactivada.',
            'account' => $this->payload($account),
            'can_operate' => PayoutAccountService::canOperate($request->user()),
        ]);
    }

    public function reactivate(Request $request, PayoutAccount $account): JsonResponse
    {
        try {
            $account = $this->wallets->reactivate($request->user(), $account);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billetera activada.',
            'account' => $this->payload($account),
            'can_operate' => PayoutAccountService::canOperate($request->user()),
        ]);
    }

    /**
     * Estado operativo propio (puede operar, deuda efectivo, membresía).
     */
    /**
     * Catálogo de tipos de billetera (para el combo: filtrar por país en el cliente).
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => \App\Models\WalletType::where('active', true)->orderBy('country')->orderBy('id')->get()->all(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user()->load(['status', 'typeUser', 'membershipSubscription']);

        return response()->json([
            'success' => true,
            'data' => \App\Support\OperatingGate::status($user),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PayoutAccount;
use App\Services\PayoutAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Billeteras de cobro del panel web. Delega en PayoutAccountService
 * (misma lógica que la app móvil).
 */
class PayoutAccountController extends Controller
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

    private function denied()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        return null;
    }

    public function index()
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'can_operate' => PayoutAccountService::canOperate(Auth::user()),
            'data' => collect($this->wallets->list(Auth::user()))->map(fn ($a) => $this->payload($a))->values()->all(),
        ]);
    }

    /**
     * Catálogo de tipos de billetera por país (para el combo).
     */
    public function types()
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'data' => \App\Models\WalletType::where('active', true)->orderBy('country')->orderBy('id')->get()->all(),
        ]);
    }

    public function store(Request $request)
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        $request->validate([
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
            $account = $this->wallets->create(Auth::user(), $request->only([
                'currency', 'country', 'wallet_type_id', 'label', 'holder_name', 'cbu', 'alias_cbu', 'account_ref', 'iban', 'bank',
            ]));
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
            'can_operate' => PayoutAccountService::canOperate(Auth::user()),
        ], 201);
    }

    public function update(Request $request, PayoutAccount $account)
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        if ((int) $account->user_id !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Esa billetera no es tuya.'], 403);
        }

        $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'cbu' => ['nullable', 'string', 'max:22'],
            'alias_cbu' => ['nullable', 'string', 'max:100'],
            'account_ref' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:34'],
            'bank' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $account = $this->wallets->update(Auth::user(), $account, $request->only([
                'label', 'holder_name', 'cbu', 'alias_cbu', 'account_ref', 'iban', 'bank',
            ]));
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

    public function setDefault(PayoutAccount $account)
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        try {
            $account = $this->wallets->setDefault(Auth::user(), $account);
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

    public function deactivate(PayoutAccount $account)
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        try {
            $account = $this->wallets->deactivate(Auth::user(), $account);
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
            'can_operate' => PayoutAccountService::canOperate(Auth::user()),
        ]);
    }

    public function reactivate(PayoutAccount $account)
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        try {
            $account = $this->wallets->reactivate(Auth::user(), $account);
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
            'can_operate' => PayoutAccountService::canOperate(Auth::user()),
        ]);
    }

    /**
     * Estado operativo propio + datos de la matriz para pagar.
     */
    public function status()
    {
        if ($denied = $this->denied()) {
            return $denied;
        }

        $user = Auth::user()->load(['status', 'typeUser', 'membershipSubscription']);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                \App\Support\OperatingGate::status($user),
                ['matrix' => \App\Models\CompanySetting::orderBy('key')->pluck('value', 'key')]
            ),
        ]);
    }
}

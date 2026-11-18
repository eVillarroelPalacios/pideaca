<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\FeeRule;
use App\Models\LedgerEntry;
use App\Models\MembershipInvoice;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Settlement;
use App\Models\User;
use App\Services\BillingService;
use App\Services\MembershipService;
use App\Support\OperatingGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Backoffice de dinero - Fase 1 (solo Admin).
 *
 * Comisiones, matriz PideAca, liquidaciones manuales, libro y
 * suscripciones Hogar. Todo responde JSON para el panel web.
 */
class AdminBillingController extends Controller
{
    private function deny()
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        return null;
    }

    // ---------- Reglas de comisión ----------

    public function feeRules()
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'data' => FeeRule::orderBy('module')->orderBy('id')->get()->all(),
        ]);
    }

    public function storeFeeRule(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'module' => ['required', 'string', 'in:mercado,hogar'],
            'country' => ['nullable', 'string', 'size:2'],
            'currency' => ['nullable', 'string', 'size:3'],
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'min_fee' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $rule = FeeRule::create([
            'module' => $data['module'],
            'country' => isset($data['country']) && $data['country'] !== '' ? strtoupper($data['country']) : null,
            'currency' => isset($data['currency']) && $data['currency'] !== '' ? strtoupper($data['currency']) : null,
            'percent' => $data['percent'],
            'fixed_fee' => $data['fixed_fee'] ?? 0,
            'min_fee' => $data['min_fee'] ?? 0,
            'active' => (bool) ($data['active'] ?? true),
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to' => $data['valid_to'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Regla creada correctamente.',
            'rule' => $rule,
        ], 201);
    }

    public function updateFeeRule(Request $request, FeeRule $rule)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'fixed_fee' => ['sometimes', 'numeric', 'min:0'],
            'min_fee' => ['sometimes', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'valid_from' => ['sometimes', 'nullable', 'date'],
            'valid_to' => ['sometimes', 'nullable', 'date'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ]);

        if (array_key_exists('country', $data)) {
            $data['country'] = $data['country'] !== null && $data['country'] !== '' ? strtoupper($data['country']) : null;
        }

        if (array_key_exists('currency', $data)) {
            $data['currency'] = $data['currency'] !== null && $data['currency'] !== '' ? strtoupper($data['currency']) : null;
        }

        $rule->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Regla actualizada. Rige para cobros nuevos.',
            'rule' => $rule->fresh(),
        ]);
    }

    // ---------- Matriz PideAca ----------

    public function companyMatrix()
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'data' => CompanySetting::orderBy('key')->pluck('value', 'key'),
        ]);
    }

    public function updateCompanyMatrix(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            CompanySetting::set($key, $value !== '' ? $value : null);
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos de la cuenta matriz actualizados.',
            'data' => CompanySetting::orderBy('key')->pluck('value', 'key'),
        ]);
    }

    // ---------- Liquidaciones ----------

    public function settlements(Request $request, BillingService $billing)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $query = Settlement::with('user:id,name,email')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(100)->get(),
        ]);
    }

    public function createSettlement(Request $request, BillingService $billing)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'charge_ids' => ['required', 'array', 'min:1'],
            'charge_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::findOrFail((int) $data['user_id']);

        try {
            $settlement = $billing->createSettlement(
                $user,
                strtoupper($data['currency'] ?? 'ARS'),
                array_map('intval', $data['charge_ids']),
                Auth::user(),
                $data['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Liquidación creada.',
            'settlement' => $settlement->load('items', 'user:id,name,email'),
        ], 201);
    }

    public function paySettlement(Request $request, Settlement $settlement, BillingService $billing)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
        ], [
            'reference.required' => 'Ingresá el comprobante de la transferencia.',
        ]);

        try {
            $settlement = $billing->paySettlement($settlement, $data['reference'], Auth::user());
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Liquidación pagada.',
            'settlement' => $settlement->load('items', 'user:id,name,email'),
        ]);
    }

    public function cancelSettlement(Settlement $settlement, BillingService $billing)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'message' => 'Liquidación cancelada.',
            'settlement' => $billing->cancelSettlement($settlement)->load('items'),
        ]);
    }

    public function ledger(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $query = LedgerEntry::with('user:id,name,email')->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->input('currency')));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(200)->get(),
        ]);
    }

    public function operatingStatus(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $user = $request->filled('user_id')
            ? User::with(['status', 'typeUser', 'membershipSubscription'])->findOrFail((int) $request->input('user_id'))
            : Auth::user()->load(['status', 'typeUser', 'membershipSubscription']);

        return response()->json([
            'success' => true,
            'data' => OperatingGate::status($user, strtoupper($request->input('currency', 'ARS'))),
        ]);
    }

    // ---------- Tipos de billetera por país ----------

    public function walletTypes()
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'data' => \App\Models\WalletType::orderBy('country')->orderBy('id')->get()->all(),
        ]);
    }

    public function storeWalletType(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'country' => ['required', 'string', 'size:2'],
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:100'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string', 'max:30'],
            'active' => ['nullable', 'boolean'],
        ]);

        $exists = \App\Models\WalletType::where('country', strtoupper($data['country']))
            ->where('code', strtolower(trim((string) $data['code'])))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe ese tipo para el país.',
            ], 422);
        }

        $type = \App\Models\WalletType::create([
            'country' => strtoupper($data['country']),
            'code' => strtolower(trim((string) $data['code'])),
            'name' => trim($data['name']),
            'fields' => $data['fields'] ?? [],
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de billetera creado.',
            'wallet_type' => $type,
        ], 201);
    }

    public function updateWalletType(Request $request, \App\Models\WalletType $walletType)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'fields' => ['sometimes', 'nullable', 'array'],
            'fields.*' => ['string', 'max:30'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        $walletType->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de billetera actualizado.',
            'wallet_type' => $walletType->fresh(),
        ]);
    }

    // ---------- Cobros manuales ----------

    public function charges(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $query = \App\Models\Charge::with('creator:id,name')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(200)->get(),
        ]);
    }

    public function registerCharge(Request $request, BillingService $billing)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'module' => ['required', 'string', 'in:mercado,hogar'],
            'order_type' => ['required', 'string', 'in:order,service_order'],
            'order_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'in:transferencia,efectivo'],
            'reference' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        try {
            $charge = $billing->registerManualCharge(
                $data['module'],
                $data['order_type'],
                (int) $data['order_id'],
                (float) $data['amount'],
                $data['method'],
                $data['reference'] ?? null,
                strtoupper($data['currency'] ?? 'ARS'),
                Auth::user()
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobro registrado.',
            'charge' => $charge,
        ], 201);
    }

    public function verifyCharge(\App\Models\Charge $charge, BillingService $billing)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        try {
            $charge = $billing->verifyTransferCharge($charge, Auth::user());
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobro verificado.',
            'charge' => $charge,
        ]);
    }

    // ---------- Planes y suscripciones Hogar ----------

    public function plans()
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'data' => \App\Models\MembershipPlan::orderBy('id')->get()->all(),
        ]);
    }

    public function storePlan(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_days' => ['nullable', 'integer', 'min:1'],
            'grace_days' => ['nullable', 'integer', 'min:1'],
            'active' => ['nullable', 'boolean'],
        ]);

        $plan = \App\Models\MembershipPlan::create([
            'name' => trim($data['name']),
            'module' => 'hogar',
            'currency' => strtoupper($data['currency'] ?? 'ARS'),
            'price' => $data['price'],
            'billing_days' => (int) ($data['billing_days'] ?? 30),
            'grace_days' => (int) ($data['grace_days'] ?? 15),
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan creado correctamente.',
            'plan' => $plan,
        ], 201);
    }

    public function updatePlan(Request $request, \App\Models\MembershipPlan $plan)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'billing_days' => ['sometimes', 'integer', 'min:1'],
            'grace_days' => ['sometimes', 'integer', 'min:1'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        $plan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Plan actualizado. Rige para facturas futuras.',
            'plan' => $plan->fresh(),
        ]);
    }

    public function invoices(Request $request)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $query = MembershipInvoice::with('user:id,name,email')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(200)->get(),
        ]);
    }

    public function payInvoice(Request $request, MembershipInvoice $invoice, MembershipService $membership)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Factura marcada como pagada.',
            'invoice' => $membership->payInvoice($invoice, $data['reference'] ?? null)->load('user:id,name,email'),
        ]);
    }

    public function subscribe(Request $request, MembershipService $membership)
    {
        if ($denied = $this->deny()) {
            return $denied;
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
        ]);

        try {
            $invoice = $membership->subscribe(
                User::findOrFail((int) $data['user_id']),
                \App\Models\MembershipPlan::findOrFail((int) $data['plan_id'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Suscripción creada con su primera factura.',
            'invoice' => $invoice->load('user:id,name,email'),
        ], 201);
    }
}

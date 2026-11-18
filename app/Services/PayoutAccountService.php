<?php

namespace App\Services;

use App\Models\PayoutAccount;
use App\Models\User;
use App\Models\WalletType;
use App\Support\BankAccountValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Billeteras de cobro del usuario (múltiples por moneda, una predeterminada).
 *
 * El alta exige datos bancarios válidos (CBU/alias/IBAN) y la primera cuenta
 * queda como predeterminada. Siempre hay como máximo una default por
 * usuario+moneda. No se borra con historial: se desactiva.
 */
class PayoutAccountService
{
    public function list(User $user): array
    {
        return $user->payoutAccounts()->orderByDesc('is_default')->orderBy('id')->get()->all();
    }

    /**
     * @param array{currency?:string,country?:string,wallet_type_id?:int,label?:?string,holder_name:string,cbu?:?string,alias_cbu?:?string,account_ref?:?string,iban?:?string,bank?:?string} $data
     */
    public function create(User $user, array $data): PayoutAccount
    {
        $type = $this->resolveType($data['wallet_type_id'] ?? null, true);
        $attrs = $this->validatedAttributes($data, false, $type);

        return DB::transaction(function () use ($user, $attrs) {
            $exists = PayoutAccount::where('user_id', $user->id)
                ->where('currency', $attrs['currency'])
                ->exists();

            $attrs['is_default'] = ! $exists;

            return tap($user->payoutAccounts()->create($attrs), fn ($a) => $a->refresh());
        });
    }

    public function update(User $user, PayoutAccount $account, array $data): PayoutAccount
    {
        $this->assertOwner($user, $account);

        $type = $this->resolveType($data['wallet_type_id'] ?? null, false)
            ?? $account->walletType;
        $attrs = $this->validatedAttributes($data, true, $type);

        return DB::transaction(function () use ($account, $attrs) {
            unset($attrs['is_default'], $attrs['currency'], $attrs['country'], $attrs['wallet_type_id']);
            $account->update($attrs);

            return $account->fresh();
        });
    }

    /**
     * Resuelve el tipo de billetera (debe existir y estar activo para altas).
     */
    private function resolveType($id, bool $forCreate): ?WalletType
    {
        if ($id === null || $id === '') {
            return null;
        }

        $type = WalletType::find((int) $id);

        if (! $type || ($forCreate && ! $type->active)) {
            throw ValidationException::withMessages([
                'wallet_type_id' => 'El tipo de billetera no es válido.',
            ]);
        }

        return $type;
    }

    public function setDefault(User $user, PayoutAccount $account): PayoutAccount
    {
        $this->assertOwner($user, $account);
        $account->refresh();

        return DB::transaction(function () use ($user, $account) {
            if (! $account->is_active) {
                throw ValidationException::withMessages([
                    'account' => 'La billetera está desactivada.',
                ]);
            }

            PayoutAccount::where('user_id', $user->id)
                ->where('currency', $account->currency)
                ->whereKeyNot($account->getKey())
                ->update(['is_default' => false]);

            $account->update(['is_default' => true]);

            return $account->fresh();
        });
    }

    public function deactivate(User $user, PayoutAccount $account): PayoutAccount
    {
        $this->assertOwner($user, $account);
        $account->refresh();

        return DB::transaction(function () use ($user, $account) {
            if ($account->is_default) {
                $other = PayoutAccount::where('user_id', $user->id)
                    ->where('currency', $account->currency)
                    ->whereKeyNot($account->getKey())
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->first();

                if (! $other) {
                    throw ValidationException::withMessages([
                        'account' => 'Es la única billetera activa: agregá otra antes de desactivarla.',
                    ]);
                }

                $other->update(['is_default' => true]);
            }

            $account->update(['is_default' => false, 'is_active' => false]);

            return $account->fresh();
        });
    }

    public function reactivate(User $user, PayoutAccount $account): PayoutAccount
    {
        $this->assertOwner($user, $account);

        $account->update(['is_active' => true]);

        return $account->fresh();
    }

    /**
     * ¿Puede operar? Al menos una billetera activa (cualquier moneda).
     */
    public static function canOperate(User $user): bool
    {
        return PayoutAccount::where('user_id', $user->id)->where('is_active', true)->exists();
    }

    private function assertOwner(User $user, PayoutAccount $account): void
    {
        if ((int) $account->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'account' => 'Esa billetera no es tuya.',
            ]);
        }
    }

    /**
     * Valida y normaliza según el tipo de billetera (o genérico sin tipo).
     */
    private function validatedAttributes(array $data, bool $partial, ?WalletType $type = null): array
    {
        if ($type && ! array_key_exists('currency', $data)) {
            $data['currency'] = $type->country === 'ES' ? 'EUR' : 'ARS';
        }

        $attrs = [
            'wallet_type_id' => $type ? $type->getKey() : null,
            'country' => $type ? $type->country : strtoupper(trim((string) ($data['country'] ?? 'AR'))),
            'currency' => strtoupper(trim((string) ($data['currency'] ?? 'ARS'))),
            'label' => isset($data['label']) ? trim((string) $data['label']) : null,
            'holder_name' => trim((string) ($data['holder_name'] ?? '')),
            'cbu' => isset($data['cbu']) && $data['cbu'] !== null && $data['cbu'] !== '' ? preg_replace('/\D/', '', (string) $data['cbu']) : null,
            'alias_cbu' => isset($data['alias_cbu']) && $data['alias_cbu'] !== null && $data['alias_cbu'] !== '' ? trim((string) $data['alias_cbu']) : null,
            'account_ref' => isset($data['account_ref']) && $data['account_ref'] !== null && $data['account_ref'] !== '' ? trim((string) $data['account_ref']) : null,
            'iban' => isset($data['iban']) && $data['iban'] !== null && $data['iban'] !== '' ? strtoupper(preg_replace('/\s+/', '', (string) $data['iban'])) : null,
            'bank' => isset($data['bank']) && $data['bank'] !== null && $data['bank'] !== '' ? trim((string) $data['bank']) : null,
        ];

        if (! $partial || array_key_exists('holder_name', $data)) {
            if ($attrs['holder_name'] === '') {
                throw ValidationException::withMessages([
                    'holder_name' => 'Ingresá el titular de la cuenta.',
                ]);
            }
        }

        if ($type) {
            $this->validateForType($attrs, $data, $type, $partial);
        } else {
            $this->validateGeneric($attrs, $data, $partial);
        }

        if ($partial) {
            // En edición parcial solo se tocan los campos enviados.
            foreach (['label', 'holder_name', 'cbu', 'alias_cbu', 'account_ref', 'iban', 'bank'] as $k) {
                if (! array_key_exists($k, $data)) {
                    unset($attrs[$k]);
                }
            }
            unset($attrs['currency'], $attrs['country'], $attrs['wallet_type_id']);
        }

        return $attrs;
    }

    /**
     * Reglas según el tipo del catálogo.
     */
    private function validateForType(array &$attrs, array $data, WalletType $type, bool $partial): void
    {
        $need = function (string $key) use ($data, $partial) {
            return ! $partial || array_key_exists($key, $data);
        };

        $code = $type->code;

        if ($code === 'transferencia' && $type->country === 'AR') {
            if ($need('cbu') && ($attrs['cbu'] === null || ! BankAccountValidator::validCbu($attrs['cbu']))) {
                throw ValidationException::withMessages(['cbu' => 'El CBU no es válido (verificá los 22 dígitos).']);
            }
            if ($need('alias_cbu') && $attrs['alias_cbu'] !== null && ! BankAccountValidator::validAlias($attrs['alias_cbu'])) {
                throw ValidationException::withMessages(['alias_cbu' => 'El alias debe tener de 6 a 20 caracteres (letras, números, punto o guion).']);
            }
            $attrs['iban'] = null;
            $attrs['account_ref'] = null;
        } elseif ($code === 'mercadopago') {
            if ($need('alias_cbu') && $attrs['alias_cbu'] === null) {
                throw ValidationException::withMessages(['alias_cbu' => 'Ingresá tu alias de Mercado Pago o el CVU.']);
            }
            if ($need('alias_cbu') && $attrs['alias_cbu'] !== null) {
                if (ctype_digit($attrs['alias_cbu']) && strlen($attrs['alias_cbu']) === 22) {
                    if (! BankAccountValidator::validCbu($attrs['alias_cbu'])) {
                        throw ValidationException::withMessages(['alias_cbu' => 'El CVU no es válido (verificá los 22 dígitos).']);
                    }
                } elseif (! BankAccountValidator::validAlias($attrs['alias_cbu'])) {
                    throw ValidationException::withMessages(['alias_cbu' => 'El alias debe tener de 6 a 20 caracteres (letras, números, punto o guion).']);
                }
            }
            $attrs['cbu'] = null;
            $attrs['iban'] = null;
        } elseif ($code === 'transferencia' && $type->country === 'ES') {
            if ($need('iban') && ($attrs['iban'] === null || ! BankAccountValidator::validIban($attrs['iban'], 'ES'))) {
                throw ValidationException::withMessages(['iban' => 'El IBAN no es válido.']);
            }
            $attrs['cbu'] = null;
            $attrs['account_ref'] = null;
        } elseif ($code === 'paypal') {
            if ($need('account_ref') && ($attrs['account_ref'] === null || ! filter_var($attrs['account_ref'], FILTER_VALIDATE_EMAIL))) {
                throw ValidationException::withMessages(['account_ref' => 'Ingresá el email de tu cuenta PayPal.']);
            }
            $attrs['cbu'] = null;
            $attrs['iban'] = null;
        } elseif ($code === 'bizum') {
            $phone = $attrs['account_ref'] !== null ? preg_replace('/[\s\-]/', '', $attrs['account_ref']) : null;
            if ($need('account_ref') && ($phone === null || ! preg_match('/^\+?\d{6,15}$/', $phone))) {
                throw ValidationException::withMessages(['account_ref' => 'Ingresá el celular asociado a Bizum.']);
            }
            $attrs['account_ref'] = $phone;
            $attrs['cbu'] = null;
            $attrs['iban'] = null;
        }

        $attrs['account_ref'] = $attrs['account_ref'] ?? null;
    }

    /**
     * Sin tipo: al menos un medio válido (comportamiento original).
     */
    private function validateGeneric(array &$attrs, array $data, bool $partial): void
    {
        $need = function (string $key) use ($data, $partial) {
            return ! $partial || array_key_exists($key, $data);
        };

        $hasCbu = $attrs['cbu'] !== null;
        $hasIban = $attrs['iban'] !== null;

        if (! $partial && ! $hasCbu && ! $hasIban) {
            throw ValidationException::withMessages([
                'cbu' => 'Ingresá un CBU o un IBAN válido.',
            ]);
        }

        if ($need('cbu') && $hasCbu && ! BankAccountValidator::validCbu($attrs['cbu'])) {
            throw ValidationException::withMessages(['cbu' => 'El CBU no es válido (verificá los 22 dígitos).']);
        }

        if ($need('alias_cbu') && $attrs['alias_cbu'] !== null && ! BankAccountValidator::validAlias($attrs['alias_cbu'])) {
            throw ValidationException::withMessages(['alias_cbu' => 'El alias debe tener de 6 a 20 caracteres (letras, números, punto o guion).']);
        }

        if ($need('iban') && $hasIban && ! BankAccountValidator::validIban($attrs['iban'])) {
            throw ValidationException::withMessages(['iban' => 'El IBAN no es válido.']);
        }
    }
}

<?php

namespace App\Services;

use App\Models\PayoutAccount;
use App\Models\User;
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
     * @param array{currency?:string,label?:?string,holder_name:string,cbu?:?string,alias_cbu?:?string,iban?:?string,bank?:?string} $data
     */
    public function create(User $user, array $data): PayoutAccount
    {
        $attrs = $this->validatedAttributes($data);

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

        $attrs = $this->validatedAttributes($data, true);

        return DB::transaction(function () use ($account, $attrs) {
            unset($attrs['is_default'], $attrs['currency']);
            $account->update($attrs);

            return $account->fresh();
        });
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
     * Valida y normaliza (CBU/alias/IBAN). Al menos un medio válido.
     */
    private function validatedAttributes(array $data, bool $partial = false): array
    {
        $attrs = [
            'currency' => strtoupper(trim((string) ($data['currency'] ?? 'ARS'))),
            'label' => isset($data['label']) ? trim((string) $data['label']) : null,
            'holder_name' => trim((string) ($data['holder_name'] ?? '')),
            'cbu' => isset($data['cbu']) && $data['cbu'] !== null && $data['cbu'] !== '' ? preg_replace('/\D/', '', (string) $data['cbu']) : null,
            'alias_cbu' => isset($data['alias_cbu']) && $data['alias_cbu'] !== null && $data['alias_cbu'] !== '' ? trim((string) $data['alias_cbu']) : null,
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

        if (! $partial) {
            $hasCbu = $attrs['cbu'] !== null;
            $hasIban = $attrs['iban'] !== null;

            if (! $hasCbu && ! $hasIban) {
                throw ValidationException::withMessages([
                    'cbu' => 'Ingresá un CBU o un IBAN válido.',
                ]);
            }

            if ($hasCbu && ! BankAccountValidator::validCbu($attrs['cbu'])) {
                throw ValidationException::withMessages([
                    'cbu' => 'El CBU no es válido (verificá los 22 dígitos).',
                ]);
            }

            if ($attrs['alias_cbu'] !== null && ! BankAccountValidator::validAlias($attrs['alias_cbu'])) {
                throw ValidationException::withMessages([
                    'alias_cbu' => 'El alias debe tener de 6 a 20 caracteres (letras, números, punto o guion).',
                ]);
            }

            if ($hasIban && ! BankAccountValidator::validIban($attrs['iban'])) {
                throw ValidationException::withMessages([
                    'iban' => 'El IBAN no es válido.',
                ]);
            }
        }

        if ($partial) {
            // En edición parcial solo se tocan los campos enviados.
            foreach (['label', 'holder_name', 'cbu', 'alias_cbu', 'iban', 'bank'] as $k) {
                if (! array_key_exists($k, $data)) {
                    unset($attrs[$k]);
                }
            }
            unset($attrs['currency']);

            if (array_key_exists('cbu', $attrs) && $attrs['cbu'] !== null && ! BankAccountValidator::validCbu($attrs['cbu'])) {
                throw ValidationException::withMessages(['cbu' => 'El CBU no es válido (verificá los 22 dígitos).']);
            }

            if (array_key_exists('alias_cbu', $attrs) && $attrs['alias_cbu'] !== null && ! BankAccountValidator::validAlias($attrs['alias_cbu'])) {
                throw ValidationException::withMessages(['alias_cbu' => 'El alias debe tener de 6 a 20 caracteres (letras, números, punto o guion).']);
            }

            if (array_key_exists('iban', $attrs) && $attrs['iban'] !== null && ! BankAccountValidator::validIban($attrs['iban'])) {
                throw ValidationException::withMessages(['iban' => 'El IBAN no es válido.']);
            }
        }

        return $attrs;
    }
}

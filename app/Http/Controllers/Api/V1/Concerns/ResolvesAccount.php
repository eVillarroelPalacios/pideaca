<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Provider;
use App\Models\User;

/**
 * Reglas de cuenta compartidas por los endpoints del perfil movil.
 *
 * Una cuenta es el conjunto de usuarios que comparten el mismo email. El
 * principal es el de menor id (igual que en la web) y los secundarios heredan
 * negocio, direccion, rol y paginas cuando no tienen datos propios.
 */
trait ResolvesAccount
{
    protected function accountPrincipal(User $user): ?User
    {
        $email = strtolower(trim((string) $user->email));

        if ($email === '') {
            return null;
        }

        $principalId = User::whereRaw('LOWER(email) = ?', [$email])->min('id');

        if (! $principalId) {
            return null;
        }

        if ((int) $principalId === (int) $user->id) {
            return $user;
        }

        return User::find($principalId);
    }

    protected function accountProvider(User $user): ?Provider
    {
        if ($user->provider) {
            return $user->provider;
        }

        $principal = $this->accountPrincipal($user);

        if (! $principal || (int) $principal->id === (int) $user->id) {
            return null;
        }

        return $principal->provider;
    }

    protected function accountUserIds(User $user): array
    {
        $email = strtolower(trim((string) $user->email));

        if ($email === '') {
            return [$user->id];
        }

        return User::whereRaw('LOWER(email) = ?', [$email])
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }
}

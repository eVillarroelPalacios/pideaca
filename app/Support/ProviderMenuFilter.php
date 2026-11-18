<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Filtra los módulos del menú según las categorías del prestador.
 *
 * Las páginas asignadas (page_user) siguen siendo la base; acá solo se
 * ocultan módulos completos de grupos que el prestador NO eligió en
 * "Categorías". Se calcula en cada request: si cambia sus categorías,
 * el menú se refresca solo. Sin prestador, sin categorías elegidas o
 * si no es Prestador, devuelve los módulos intactos.
 */
class ProviderMenuFilter
{
    public static function filter($user, Collection $modules): Collection
    {
        if (! $user || strcasecmp((string) ($user->typeUser?->description ?? ''), 'Prestador') !== 0) {
            return $modules;
        }

        $provider = $user->provider ?? null;

        if (! $provider) {
            return $modules;
        }

        $groups = $provider->subgroups()
            ->with('group')
            ->get()
            ->map(fn ($s) => mb_strtolower(trim((string) ($s->group?->description ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($groups === []) {
            return $modules;
        }

        $map = config('provider_menu.groups', []);
        $always = array_map(fn ($m) => mb_strtolower(trim((string) $m)), (array) config('provider_menu.always_visible', []));

        // Módulo => grupos que lo reclaman (solo mapeados).
        $claimedBy = [];
        foreach ($map as $group => $mods) {
            foreach ((array) $mods as $mod) {
                $claimedBy[mb_strtolower(trim((string) $mod))][] = mb_strtolower(trim((string) $group));
            }
        }

        return $modules->filter(function ($module) use ($groups, $claimedBy, $always) {
            $name = mb_strtolower(trim((string) ($module->description ?? '')));

            if (in_array($name, $always, true)) {
                return true;
            }

            if (! isset($claimedBy[$name])) {
                return true;
            }

            return count(array_intersect($claimedBy[$name], $groups)) > 0;
        })->values();
    }
}

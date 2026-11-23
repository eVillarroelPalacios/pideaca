<?php

namespace App\Support;

use App\Models\Provider;

/**
 * Clasifica al comercio del prestador por sus categorias.
 *
 * "Repartidores de confianza" (y todo el flujo de despacho con
 * repartidores) solo tiene sentido para comercios con delivery
 * (grupo Comercio). Un prestador de Hogar no debe ver esa seccion.
 *
 * Regla conservadora para no romper nada existente:
 * - Sin provider o sin categorias elegidas => true (se mantiene visible,
 *   igual que antes de este cambio).
 * - Con categorias => true solo si alguna es del grupo Comercio.
 */
class ProviderCommerce
{
    public static function isComercio(?Provider $provider): bool
    {
        if (! $provider) {
            return true;
        }

        $groups = $provider->subgroups()
            ->with('group')
            ->get()
            ->map(fn ($s) => mb_strtolower(trim((string) ($s->group?->description ?? ''))))
            ->filter()
            ->unique()
            ->values();

        if ($groups->isEmpty()) {
            return true;
        }

        // Prefijo y no igualdad exacta: cubre 'Comercio', 'Comercio & ...', etc.
        return $groups->contains(fn (string $g) => str_starts_with($g, 'comercio'));
    }
}

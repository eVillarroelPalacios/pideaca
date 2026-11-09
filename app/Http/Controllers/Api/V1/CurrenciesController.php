<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tipos de monedas de la plataforma para la app movil.
 *
 * Espeja la seccion "tipo-monedas" del panel web sin tocarla: mismo modelo
 * (Currency), mismas reglas (una moneda por pais, codigo ISO de 3 letras,
 * simbolo/posicion/decimales/separadores/locale) y mismo permiso (pagina
 * tipo-monedas). No se puede eliminar la moneda por defecto de la
 * plataforma (config `currency.default`, ARS si no se configura).
 */
class CurrenciesController extends Controller
{
    /**
     * Catalogo completo con su pais, la marca de moneda por defecto y la
     * lista de paises para armar el selector del modal.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $currencies = Currency::with('country:id,iso_code,name')
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $currencies
                ->map(fn (Currency $currency) => $this->payload($currency))
                ->values()
                ->all(),
            'filters' => [
                'countries' => Country::orderBy('name')
                    ->get(['id', 'iso_code', 'name'])
                    ->map(fn (Country $country) => [
                        'id' => $country->id,
                        'iso_code' => $country->iso_code,
                        'name' => $country->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'summary' => [
                'total' => $currencies->count(),
                'active' => $currencies->where('is_active', true)->count(),
                'default_code' => $this->defaultCode(),
            ],
        ]);
    }

    /**
     * Alta de una moneda.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate($this->rules());

        $currency = Currency::create($this->persisted($request, $data));

        return response()->json([
            'success' => true,
            'message' => 'Moneda creada correctamente.',
            'currency' => $this->payload($currency->load('country:id,iso_code,name')),
        ], 201);
    }

    /**
     * Edicion de una moneda.
     */
    public function update(Request $request, Currency $currency): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate($this->rules($currency));

        $currency->update($this->persisted($request, $data));

        return response()->json([
            'success' => true,
            'message' => 'Moneda actualizada correctamente.',
            'currency' => $this->payload($currency->load('country:id,iso_code,name')),
        ]);
    }

    /**
     * Baja de una moneda. Se bloquea la moneda por defecto de la
     * plataforma, igual que en la web.
     */
    public function destroy(Request $request, Currency $currency): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($this->isDefault($currency)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la moneda por defecto de la plataforma.',
                'code' => 'CURRENCY_IS_DEFAULT',
            ], 409);
        }

        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Moneda eliminada correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     *
     * @return array<string, mixed>
     */
    private function rules(?Currency $currency = null): array
    {
        $id = $currency?->id;

        return [
            'country_id' => [
                'required',
                'integer',
                'exists:countries,id',
                Rule::unique('currencies', 'country_id')->ignore($id),
            ],
            'code' => [
                'required',
                'string',
                'size:3',
                'alpha',
                Rule::unique('currencies', 'code')->ignore($id),
            ],
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:8',
            'symbol_position' => 'required|in:before,after',
            'symbol_space' => 'sometimes|boolean',
            'decimals' => 'required|integer|min:0|max:4',
            'decimal_separator' => 'required|string|size:1',
            'thousand_separator' => 'required|string|size:1',
            'locale' => 'required|string|max:12',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Normaliza los valores antes de persistir, igual que en la web.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function persisted(Request $request, array $data): array
    {
        return [
            'country_id' => $data['country_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'symbol_position' => $data['symbol_position'],
            'symbol_space' => (bool) $request->input('symbol_space', true),
            'decimals' => (int) $data['decimals'],
            'decimal_separator' => $data['decimal_separator'],
            'thousand_separator' => $data['thousand_separator'],
            'locale' => $data['locale'],
            'is_active' => (bool) $request->input('is_active', true),
        ];
    }

    /**
     * Permiso identico al guard web `page:tipo-monedas`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('tipo-monedas')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    private function defaultCode(): string
    {
        return strtoupper((string) config('currency.default', 'ARS'));
    }

    private function isDefault(Currency $currency): bool
    {
        return strcasecmp($currency->code, $this->defaultCode()) === 0;
    }

    /**
     * Forma publica de una moneda, con los datos que usa la tabla web.
     */
    private function payload(Currency $currency): array
    {
        $isDefault = $this->isDefault($currency);

        return [
            'id' => $currency->id,
            'country_id' => $currency->country_id,
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
            'symbol_position' => $currency->symbol_position,
            'symbol_space' => (bool) $currency->symbol_space,
            'decimals' => (int) $currency->decimals,
            'decimal_separator' => $currency->decimal_separator,
            'thousand_separator' => $currency->thousand_separator,
            'locale' => $currency->locale,
            'is_active' => (bool) $currency->is_active,
            'country' => $currency->country ? [
                'id' => $currency->country->id,
                'iso_code' => $currency->country->iso_code,
                'name' => $currency->country->name,
            ] : null,
            'is_default' => $isDefault,
            'can_delete' => ! $isDefault,
        ];
    }
}

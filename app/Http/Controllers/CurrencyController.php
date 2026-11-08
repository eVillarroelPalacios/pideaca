<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * ABM de monedas. Cada moneda pertenece a un pais (relacion 1:1 con
 * countries) y define como se muestra el dinero en la plataforma: simbolo,
 * posicion, separadores y decimales.
 *
 * La pagina administrativa que lo consume es "Tipo de monedas".
 */
class CurrencyController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $defaultCode = strtoupper((string) config('currency.default', 'ARS'));

        $currencies = Currency::with('country:id,iso_code,name')
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency) use ($defaultCode) {
                return array_merge($currency->toArray(), [
                    'is_default' => strcasecmp($currency->code, $defaultCode) === 0,
                ]);
            });

        return response()->json($currencies);
    }

    public function store(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate($this->rules());

        $currency = Currency::create($this->payload($request, $data));

        return response()->json([
            'success' => true,
            'message' => 'Moneda creada correctamente.',
            'currency' => $currency->load('country:id,iso_code,name'),
        ], 201);
    }

    public function update(Request $request, Currency $currency)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $request->validate($this->rules($currency));

        $currency->update($this->payload($request, $data));

        return response()->json([
            'success' => true,
            'message' => 'Moneda actualizada correctamente.',
            'currency' => $currency->load('country:id,iso_code,name'),
        ]);
    }

    public function destroy(Currency $currency)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $defaultCode = strtoupper((string) config('currency.default', 'ARS'));

        if (strcasecmp($currency->code, $defaultCode) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la moneda por defecto de la plataforma.',
            ]);
        }

        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Moneda eliminada correctamente.',
        ]);
    }

    /**
     * Reglas de validacion comunes a alta y edicion.
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
     * Normaliza los valores antes de persistir.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(Request $request, array $data): array
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
}

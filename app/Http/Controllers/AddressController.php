<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Direcciones del usuario logueado: alimenta el selector de entrega
     * cuando el cliente confirma un pedido.
     */
    public function mine()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $addresses = Auth::user()
            ->addresses()
            ->with(['country:id,name', 'province:id,name', 'department:id,name', 'region:id,name'])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->map(fn (Address $address) => [
                'id' => $address->id,
                'street' => $address->street,
                'number' => $address->number,
                'floor_apartment' => $address->floor_apartment,
                'postal_code' => $address->postal_code,
                'notes' => $address->notes,
                'is_primary' => (bool) $address->is_primary,
                'formatted' => $address->formatted,
            ]);

        return response()->json([
            'success' => true,
            'addresses' => $addresses,
        ]);
    }
}

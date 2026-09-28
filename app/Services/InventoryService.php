<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Provider;
use App\Models\UnitOfMeasure;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de stock compartidas entre el carrito y las ordenes automaticas de
 * suscripcion, para que ambos caminos descuenten exactamente igual.
 */
class InventoryService
{
    /**
     * Descuenta el stock de las lineas de una venta y deja el movimiento SALE.
     *
     * @param  array<int, array{product: Product, quantity: int, key: string}>  $lines
     *
     * @throws ValidationException cuando no hay stock y no se permite negativo
     */
    public function applySale(Provider $provider, Order $order, array $lines): void
    {
        if (! $provider->usesInventory()) {
            return;
        }

        foreach ($lines as $line) {
            $product = $line['product'];

            if (! $product->track_stock) {
                continue;
            }

            $unit = $this->resolveInventoryUnit($product);
            $factor = (float) $unit->base_conversion_factor;
            $base = round(((int) $line['quantity']) * $factor, 3);

            $inventory = ProductInventory::where('product_id', $product->id)
                ->where('provider_id', $provider->id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = ProductInventory::create([
                    'product_id' => $product->id,
                    'provider_id' => $provider->id,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'allow_negative_stock' => false,
                ]);
            }

            $newStock = round(((float) $inventory->current_stock) - $base, 3);

            if ($newStock < 0 && ! $inventory->allow_negative_stock) {
                throw ValidationException::withMessages([
                    $line['key'] => 'Stock insuficiente de "'.$product->name.'": hay '.$inventory->current_stock.' y se requieren '.$base.'.',
                ]);
            }

            $inventory->update(['current_stock' => $newStock]);

            InventoryMovement::create([
                'product_id' => $product->id,
                'provider_id' => $provider->id,
                'order_id' => $order->id,
                'movement_type' => 'SALE',
                'quantity' => (int) $line['quantity'],
                'unit_of_measure_id' => $unit->id,
                'unit_conversion_factor' => round($factor, 4),
                'quantity_in_base_unit' => $base,
                'notes' => null,
            ]);
        }
    }

    /**
     * Devuelve el stock de las ventas de un pedido cancelado.
     */
    public function restoreCancelledOrder(Order $order): void
    {
        $sales = InventoryMovement::where('order_id', $order->id)
            ->where('movement_type', 'SALE')
            ->lockForUpdate()
            ->get();

        if ($sales->isEmpty()) {
            return;
        }

        foreach ($sales as $sale) {
            $restore = round((float) $sale->quantity_in_base_unit, 3);

            $inventory = ProductInventory::where('product_id', $sale->product_id)
                ->where('provider_id', $sale->provider_id)
                ->lockForUpdate()
                ->first();

            if ($inventory) {
                $inventory->update([
                    'current_stock' => round(((float) $inventory->current_stock) + $restore, 3),
                ]);
            } else {
                ProductInventory::create([
                    'product_id' => $sale->product_id,
                    'provider_id' => $sale->provider_id,
                    'current_stock' => $restore,
                    'reserved_stock' => 0,
                    'allow_negative_stock' => false,
                ]);
            }

            InventoryMovement::create([
                'product_id' => $sale->product_id,
                'provider_id' => $sale->provider_id,
                'order_id' => $order->id,
                'movement_type' => 'CANCELLED_SALE',
                'quantity' => $sale->quantity,
                'unit_of_measure_id' => $sale->unit_of_measure_id,
                'unit_conversion_factor' => $sale->unit_conversion_factor,
                'quantity_in_base_unit' => $sale->quantity_in_base_unit,
                'notes' => 'Cancelacion del pedido '.$order->order_number,
            ]);
        }
    }

    private function resolveInventoryUnit(Product $product): UnitOfMeasure
    {
        $unit = $product->unitOfMeasure
            ?? UnitOfMeasure::where('name', 'Unidad')->first()
            ?? UnitOfMeasure::first();

        if (! $unit) {
            throw ValidationException::withMessages([
                'items' => 'No hay unidades de medida configuradas para descontar inventario.',
            ]);
        }

        return $unit;
    }
}

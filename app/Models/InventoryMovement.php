<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'provider_id',
        'order_id',
        'movement_type',
        'quantity',
        'unit_of_measure_id',
        'unit_conversion_factor',
        'quantity_in_base_unit',
        'notes',
    ];

    protected $casts = [
        'movement_type' => 'string',
        'quantity' => 'decimal:3',
        'unit_conversion_factor' => 'decimal:4',
        'quantity_in_base_unit' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }
}

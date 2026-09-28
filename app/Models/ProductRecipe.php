<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends Model
{
    protected $fillable = [
        'product_id',
        'supply_id',
        'quantity_required',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    /**
     * Costo de esta linea de la ficha tecnica.
     */
    public function lineCost(): float
    {
        return round((float) $this->quantity_required * (float) $this->supply->cost_per_unit, 2);
    }
}

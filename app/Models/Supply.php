<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supply extends Model
{
    protected $fillable = [
        'provider_id',
        'name',
        'unit_of_measure_id',
        'cost_per_unit',
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    /**
     * Cantidad de este insumo que se consume en una unidad del producto.
     */
    public function quantityIn(Product $product): float
    {
        return (float) ($this->recipes
            ->firstWhere('product_id', $product->id)?->quantity_required ?? 0);
    }
}

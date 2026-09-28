<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'category_id',
        'unit_of_measure_id',
        'name',
        'description',
        'price',
        'image_path',
        'is_available',
        'sort_order',
        'track_stock',
        'min_stock_alert',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
        'track_stock' => 'boolean',
        'min_stock_alert' => 'decimal:3',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class);
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    /**
     * Costo total de produccion: suma de cada insumo por la cantidad que usa
     * una unidad del producto. Sin ficha tecnica el costo es 0.
     */
    public function calculateCost(): float
    {
        return round(
            $this->recipeItems->sum(fn (ProductRecipe $item) => (float) $item->quantity_required * (float) $item->supply->cost_per_unit),
            2
        );
    }

    /**
     * Margen bruto sobre el precio de venta, en porcentaje.
     * Sin precio de venta el margen no esta definido y devuelve null.
     */
    public function calculateProfitMargin(): ?float
    {
        $precio = (float) $this->price;

        if ($precio <= 0) {
            return null;
        }

        return round((($precio - $this->calculateCost()) / $precio) * 100, 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
{
    protected $table = 'product_inventory';

    protected $fillable = [
        'product_id',
        'provider_id',
        'current_stock',
        'reserved_stock',
        'allow_negative_stock',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'reserved_stock' => 'decimal:3',
        'allow_negative_stock' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sabores que el cliente elige para su suscripcion. Si la suscripcion no tiene
 * items propios, el pedido usa los productos del plan.
 */
class CustomerSubscriptionItem extends Model
{
    protected $fillable = [
        'customer_subscription_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class, 'customer_subscription_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

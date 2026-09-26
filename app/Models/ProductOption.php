<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_group_id',
        'name',
        'extra_price',
        'is_available',
    ];

    protected $casts = [
        'extra_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'option_group_id');
    }
}

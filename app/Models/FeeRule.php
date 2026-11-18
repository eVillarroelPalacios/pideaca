<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeRule extends Model
{
    protected $fillable = [
        'module', 'country', 'currency', 'percent', 'fixed_fee',
        'min_fee', 'active', 'valid_from', 'valid_to',
    ];

    protected $casts = [
        'percent' => 'float',
        'fixed_fee' => 'float',
        'min_fee' => 'float',
        'active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];
}

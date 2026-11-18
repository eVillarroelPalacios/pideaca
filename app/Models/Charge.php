<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Charge extends Model
{
    public const STATUS_PENDING = 'pending_verification';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_TRANSFER = 'transferencia';
    public const METHOD_CASH = 'efectivo';

    protected $fillable = [
        'module', 'order_type', 'order_id', 'amount', 'currency',
        'method', 'status', 'transaction_reference', 'fee_rule_id',
        'fee_percent', 'fee_fixed', 'fee_min', 'fee', 'net', 'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'fee_percent' => 'float',
        'fee_fixed' => 'float',
        'fee_min' => 'float',
        'fee' => 'float',
        'net' => 'float',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

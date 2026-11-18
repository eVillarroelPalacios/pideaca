<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'currency', 'period_start', 'period_end', 'gross',
        'fee', 'net', 'status', 'payout_reference', 'paid_at',
        'created_by', 'notes',
    ];

    protected $casts = [
        'gross' => 'float',
        'fee' => 'float',
        'net' => 'float',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }
}

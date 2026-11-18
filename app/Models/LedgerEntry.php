<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    public const TYPE_FEE = 'fee';
    public const TYPE_SPLIT = 'split';
    public const TYPE_WALLET_DEBIT = 'wallet_debit';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_REFUND = 'refund';
    public const TYPE_SUBSCRIPTION = 'subscription';

    protected $fillable = [
        'user_id', 'type', 'currency', 'amount',
        'reference_type', 'reference_id', 'description',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

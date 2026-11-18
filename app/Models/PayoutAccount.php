<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutAccount extends Model
{
    protected $fillable = [
        'user_id', 'wallet_type_id', 'currency', 'country', 'label',
        'holder_name', 'cbu', 'alias_cbu', 'account_ref', 'iban',
        'bank', 'is_default', 'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletType(): BelongsTo
    {
        return $this->belongsTo(WalletType::class);
    }
}

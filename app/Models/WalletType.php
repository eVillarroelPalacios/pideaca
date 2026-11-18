<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletType extends Model
{
    protected $fillable = ['country', 'code', 'name', 'fields', 'active'];

    protected $casts = [
        'fields' => 'array',
        'active' => 'boolean',
    ];

    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(PayoutAccount::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';

    protected $fillable = [
        'name', 'module', 'currency', 'price',
        'billing_days', 'grace_days', 'active',
    ];

    protected $casts = [
        'price' => 'float',
        'active' => 'boolean',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(MembershipInvoice::class);
    }
}

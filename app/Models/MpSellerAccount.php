<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpSellerAccount extends Model
{
    protected $fillable = [
        'user_id',
        'mp_user_id',
        'access_token',
        'refresh_token',
        'public_key',
        'live_mode',
        'expires_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'live_mode' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

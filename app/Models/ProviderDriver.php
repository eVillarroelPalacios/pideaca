<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderDriver extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

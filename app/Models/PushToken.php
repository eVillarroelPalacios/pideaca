<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Formato de los tokens que entrega Expo (sirven para Android y iOS). */
    public static function looksValid(?string $token): bool
    {
        return is_string($token)
            && preg_match('/^(ExponentPushToken|ExpoPushToken)\[[^\]]+\]$/', $token) === 1;
    }
}

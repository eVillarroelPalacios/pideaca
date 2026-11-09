<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    use HasFactory;

    public const TYPE_ORDER_ASSIGNED = 'order_assigned';
    public const TYPE_ORDER_CONFIRMED = 'order_confirmed';
    public const TYPE_ORDER_DISPATCHED = 'order_dispatched';
    public const TYPE_ORDER_COMPLETED = 'order_completed';
    public const TYPE_ORDER_CANCELLED = 'order_cancelled';
    public const TYPE_QUOTE_PROPOSAL = 'quote_proposal';
    public const TYPE_RATING_RECEIVED = 'rating_received';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->read_at = now();
            $this->save();
        }
    }
}

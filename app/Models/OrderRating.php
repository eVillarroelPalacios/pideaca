<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'reviewer_id',
        'ratee_id',
        'stars',
        'comment',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'reviewer_id' => 'integer',
        'ratee_id' => 'integer',
        'stars' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingLog extends Model
{
    public $timestamps = false;

    public const STATUS_SENT = 'SENT';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUSES = [
        self::STATUS_SENT,
        self::STATUS_FAILED,
        self::STATUS_CONVERTED,
    ];

    protected $fillable = [
        'provider_id',
        'user_id',
        'campaign_rule_id',
        'sent_at',
        'status',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignRule::class, 'campaign_rule_id');
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Envios que cuentan como "ya avisado": un FAILED no bloquea el reintento.
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_CONVERTED]);
    }

    public function scopeSentOn(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('sent_at', $date->toDateString());
    }
}

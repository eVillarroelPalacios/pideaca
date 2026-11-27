<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $fillable = [
        'ip_hash',
        'visited_on',
        'ip',
        'country_code',
        'country_name',
        'user_agent',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'created_at' => 'datetime',
    ];

    public function scopeOnDay(Builder $query, string $day): Builder
    {
        // whereDate: tolera 'Y-m-d' y 'Y-m-d H:i:s' segun el driver.
        return $query->whereDate('visited_on', $day);
    }

    public function scopeSince(Builder $query, string $from): Builder
    {
        return $query->whereDate('visited_on', '>=', $from);
    }

    public function setVisitedOnAttribute(mixed $value): void
    {
        $this->attributes['visited_on'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : substr((string) $value, 0, 10);
    }
}

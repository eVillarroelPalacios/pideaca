<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country_id',
        'province_id',
        'department_id',
        'region_id',
        'postal_code',
        'street',
        'number',
        'floor_apartment',
        'notes',
        'latitude',
        'longitude',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_primary' => 'boolean',
    ];

    public function scopeWherePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'province_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'department_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function getFormattedAttribute(): string
    {
        $parts = array_filter([
            trim(($this->street ?? '') . ' ' . ($this->number ?? '')),
            $this->floor_apartment,
            trim(($this->postal_code ?? '') . ' ' . ($this->region->name ?? '')),
            $this->department->name ?? '',
            $this->province->name ?? '',
            $this->country->name ?? '',
        ]);

        return implode(', ', $parts);
    }
}
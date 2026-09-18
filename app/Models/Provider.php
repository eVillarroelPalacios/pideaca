<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'business_name',
        'description',
        'banner_image',
        'rating',
        'promo',
        'phone',
        'whatsapp',
        'zone',
        'hours',
        'is_active',
    ];

    protected $casts = [
        'hours' => 'array',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'category_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ProviderService::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProviderImage::class)->orderBy('sort_order');
    }

    public function subgroups()
    {
        return $this->belongsToMany(SubGroup::class, 'provider_subgroups', 'provider_id', 'subgroup_id');
    }

    public function bannerImages(): HasMany
    {
        return $this->hasMany(ProviderImage::class)
            ->where('image_type', 'banner')
            ->orderBy('sort_order');
    }

    public function primaryBannerImage()
    {
        return $this->hasOne(ProviderImage::class)
            ->where('image_type', 'banner')
            ->where('is_primary', true);
    }
}

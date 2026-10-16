<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceType extends Model
{
    public const CODE_EMERGENCY = 'EMERGENCY';

    public const CODE_SCHEDULED = 'SCHEDULED';

    public const CODE_QUOTE = 'QUOTE';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'provider_service_types', 'service_type_id', 'provider_id');
    }
}

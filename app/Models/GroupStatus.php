<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupStatus extends Model
{
    public const STATUS_ACTIVE = 'Activo';

    public const STATUS_INACTIVE = 'Desactivo';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = ['description'];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}

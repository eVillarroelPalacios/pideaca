<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubGroup extends Model
{
    protected $fillable = ['description', 'group_id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** Profesionales que ofrecen este rubro (pool de ordenes del subgrupo). */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'provider_subgroups', 'subgroup_id', 'provider_id');
    }
}

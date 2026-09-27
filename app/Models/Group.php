<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['description', 'icon', 'group_status_id'];

    public function subgroups()
    {
        return $this->hasMany(SubGroup::class);
    }

    public function groupStatus()
    {
        return $this->belongsTo(GroupStatus::class);
    }

    /**
     * Grupos visibles en el sitio publico y en los listados: se excluyen los
     * desactivados (los grupos sin estado siguen visibles).
     */
    public function scopeActive($query)
    {
        return $query->whereDoesntHave('groupStatus', function ($q) {
            $q->where('description', GroupStatus::STATUS_INACTIVE);
        });
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['description', 'icon', 'group_status_id', 'module_id'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

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
     * desactivados (los grupos sin estado siguen visibles). El estado se
     * compara por id (config/catalog.php), nunca por descripcion.
     */
    public function scopeActive($query)
    {
        $inactiveId = (int) config('catalog.group_status_inactive_id');

        return $query->where(function ($q) use ($inactiveId) {
            $q->whereNull('group_status_id')
                ->orWhere('group_status_id', '!=', $inactiveId);
        });
    }
}

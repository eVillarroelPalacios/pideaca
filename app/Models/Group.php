<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['description', 'icon'];

    public function subgroups()
    {
        return $this->hasMany(SubGroup::class);
    }
}
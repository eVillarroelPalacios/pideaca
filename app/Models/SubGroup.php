<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubGroup extends Model
{
    protected $fillable = ['description', 'group_id'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
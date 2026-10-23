<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['description', 'icon'];

    public function pages()
    {
        return $this->hasMany(Page::class, 'module_id');
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'module_id');
    }
}

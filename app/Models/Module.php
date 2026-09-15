<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['description'];

    public function pages()
    {
        return $this->hasMany(Page::class, 'module_id');
    }
}
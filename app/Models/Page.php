<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['description', 'url', 'module_id'];

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'page_user', 'page_id', 'user_id');
    }
}
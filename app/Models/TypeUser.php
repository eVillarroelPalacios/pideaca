<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeUser extends Model
{
    protected $fillable = ['description'];

    public function users()
    {
        return $this->hasMany(User::class, 'type_user_id');
    }
}
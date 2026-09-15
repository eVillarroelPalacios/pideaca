<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    public $timestamps = false;

    protected $fillable = ['status'];

    public function users()
    {
        return $this->hasMany(User::class, 'user_status_id');
    }
}
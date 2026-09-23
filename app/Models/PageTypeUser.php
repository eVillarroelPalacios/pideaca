<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageTypeUser extends Model
{
    protected $table = 'paginasptiposusuarios';

    protected $fillable = [
        'type_user_id',
        'page_id',
    ];

    public function typeUser()
    {
        return $this->belongsTo(TypeUser::class, 'type_user_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}

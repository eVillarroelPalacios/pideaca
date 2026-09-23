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

    public function pageTypeUsers()
    {
        return $this->hasMany(PageTypeUser::class, 'type_user_id');
    }

    public function assignedPages()
    {
        return $this->belongsToMany(Page::class, 'paginasptiposusuarios', 'type_user_id', 'page_id')->withTimestamps();
    }

    public function pages()
    {
        return $this->assignedPages();
    }
}
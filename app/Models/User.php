<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_status_id',
        'type_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'user_status_id');
    }

    public function typeUser()
    {
        return $this->belongsTo(TypeUser::class, 'type_user_id');
    }

    public function pages()
    {
        return $this->belongsToMany(Page::class, 'page_user', 'user_id', 'page_id');
    }

    public function typeAssignedPages()
    {
        return $this->typeUser
            ? $this->typeUser->assignedPages
            : collect();
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(Address::class)
            ->wherePrimary()
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class);
    }
}

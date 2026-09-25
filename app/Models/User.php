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
        'email_verified_at',
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

    /**
     * Lowercase status names that grant access to the advertising module.
     */
    public const ADVERTISING_STATUSES = ['activo', 'prueba'];

    /**
     * Only Prestador users with an Activo/Prueba status can have advertising.
     */
    public function canAdvertise(): bool
    {
        $type = $this->typeUser ? strtolower(trim($this->typeUser->description)) : '';
        $status = $this->status ? strtolower(trim($this->status->status)) : '';

        return $type === 'prestador' && in_array($status, self::ADVERTISING_STATUSES, true);
    }

    /**
     * Query-level version of canAdvertise(), for filtering providers on the public site.
     */
    public function scopeAdvertisable($query)
    {
        $statuses = self::ADVERTISING_STATUSES;

        return $query
            ->whereHas('typeUser', function ($q) {
                $q->whereRaw('LOWER(description) = ?', ['prestador']);
            })
            ->whereHas('status', function ($q) use ($statuses) {
                $q->whereRaw('LOWER(status) IN ('.implode(', ', array_fill(0, count($statuses), '?')).')', $statuses);
            });
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

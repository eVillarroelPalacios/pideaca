<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInquiry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'full_name',
        'email',
        'inquiry_type',
        'message',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

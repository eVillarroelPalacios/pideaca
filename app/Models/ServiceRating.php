<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Calificacion bilateral de una orden de servicio: el cliente valora al
 * prestador (ratee = usuario del comercio) y el prestador califica al
 * cliente (ratee = usuario cliente). Una calificacion por autor y orden.
 */
class ServiceRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'reviewer_id',
        'ratee_id',
        'stars',
        'comment',
    ];

    protected $casts = [
        'service_order_id' => 'integer',
        'reviewer_id' => 'integer',
        'ratee_id' => 'integer',
        'stars' => 'integer',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipInvoice extends Model
{
    protected $table = 'membership_invoices';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'membership_plan_id', 'plan_name', 'amount',
        'currency', 'period_start', 'period_end', 'due_date', 'status',
        'reminders_sent', 'last_reminder_at', 'paid_at', 'payment_reference',
    ];

    protected $casts = [
        'amount' => 'float',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'last_reminder_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }
}

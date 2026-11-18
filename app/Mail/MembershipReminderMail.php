<?php

namespace App\Mail;

use App\Models\MembershipInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public MembershipInvoice $invoice;
    public int $overdueDays;
    public int $graceDays;

    public function __construct(string $name, MembershipInvoice $invoice, int $overdueDays, int $graceDays)
    {
        $this->name = $name;
        $this->invoice = $invoice;
        $this->overdueDays = $overdueDays;
        $this->graceDays = $graceDays;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu suscripción PideAca está pendiente de pago',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.membership-reminder');
    }
}

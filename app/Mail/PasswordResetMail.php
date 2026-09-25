<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Cuentas que comparten este correo: cada una con su nombre, tipo y enlace.
     *
     * @var array<int, array{name: string, type: string, url: string}>
     */
    public array $accounts;

    public function __construct(array $accounts)
    {
        $this->accounts = $accounts;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperá tu contraseña - PideAca',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset');
    }
}

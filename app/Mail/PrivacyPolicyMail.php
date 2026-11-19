<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivacyPolicyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Política de Privacidad y Protección de Datos - Restaurante Las Brazas',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.privacy-policy',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

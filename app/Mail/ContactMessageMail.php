<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{name:string,email:string,phone:string,subject:string,message:string,sent_at:string,ip?:string} $contactData
     */
    public function __construct(public array $contactData)
    {
    }

    public function envelope(): Envelope
    {
        $subjectCategory = $this->contactData['subject'] ?? 'Consulta General';
        $senderName = $this->contactData['name'] ?? 'Comensal';

        return new Envelope(
            replyTo: [
                new Address($this->contactData['email'], $senderName),
            ],
            subject: "[Contáctenos Web - {$subjectCategory}] {$senderName} — Restaurante Las Brazas",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

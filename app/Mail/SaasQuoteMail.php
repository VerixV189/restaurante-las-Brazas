<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaasQuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{restaurant_name:string,contact_name:string,contact_phone:string,contact_email:?string,selected_plan:string,cloud_provider:string,branches_count:int,message:?string,sent_at:string} $quoteData
     */
    public function __construct(public array $quoteData)
    {
    }

    public function envelope(): Envelope
    {
        $replyTo = [];
        if (!empty($this->quoteData['contact_email'])) {
            $replyTo[] = new Address(
                $this->quoteData['contact_email'],
                $this->quoteData['contact_name'] ?? 'Prospecto Comercial'
            );
        }

        $restaurant = $this->quoteData['restaurant_name'] ?? 'Restaurante';

        return new Envelope(
            replyTo: $replyTo,
            subject: "[Cotización SaaS - BrazasTech] {$restaurant} ({$this->quoteData['selected_plan']})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.saas-quote',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

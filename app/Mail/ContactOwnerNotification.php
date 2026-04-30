<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactOwnerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('app.noreply_email'), config('app.name')),
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: "New message from {$this->senderName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-owner',
        );
    }
}

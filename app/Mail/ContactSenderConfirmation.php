<?php

namespace App\Mail;

use App\Models\HomeSetting;
use App\Models\Work;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSenderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public readonly Collection $featuredWorks;

    /** @var array<int, string>|null */
    public readonly ?array $skills;

    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
    ) {
        $this->featuredWorks = Work::published()->active()->featured()->ordered()
            ->limit(3)
            ->get(['title', 'slug', 'excerpt', 'url']);

        $this->skills = HomeSetting::current()->skills;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('app.email'), config('app.name')),
            subject: "Thanks for reaching out, {$this->senderName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-sender',
        );
    }
}

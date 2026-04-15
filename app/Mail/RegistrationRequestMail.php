<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $requestedAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New User Registration Request');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-request',
            with: [
                'username' => $this->username,
                'email' => $this->email,
                'requestedAt' => $this->requestedAt,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

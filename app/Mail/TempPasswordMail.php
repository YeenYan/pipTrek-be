<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TempPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tempPassword,
        public readonly string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Temporary Password');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temp-password',
            with: ['tempPassword' => $this->tempPassword, 'userName' => $this->userName],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
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

    public function __construct(
        public readonly string $token,
        public readonly string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Password Reset Request');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: ['token' => $this->token, 'userName' => $this->userName],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
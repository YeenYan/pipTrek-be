# Full Source Code — Mail Classes

## `app/Mail/OtpMail.php`

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Authentication OTP Code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: ['otp' => $this->otp, 'userName' => $this->userName],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

---

## `app/Mail/TempPasswordMail.php`

```php
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
```

---

## `app/Mail/PasswordResetMail.php`

```php
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
```

---

## `app/Mail/RegistrationRequestMail.php`

```php
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
```

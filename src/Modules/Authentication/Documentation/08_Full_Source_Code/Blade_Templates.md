# Full Source Code — Blade Templates

## `resources/views/emails/otp.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>OTP Verification</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                padding: 40px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .otp-code {
                font-size: 32px;
                font-weight: bold;
                color: #2d3748;
                letter-spacing: 8px;
                text-align: center;
                padding: 20px;
                background: #f7fafc;
                border-radius: 8px;
                margin: 20px 0;
            }
            .warning {
                color: #e53e3e;
                font-size: 14px;
                margin-top: 20px;
            }
            h2 {
                color: #2d3748;
            }
            p {
                color: #4a5568;
                line-height: 1.6;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Hello, {{ $userName }}!</h2>
            <p>
                You have requested to verify your identity. Please use the
                following OTP code to complete your authentication:
            </p>

            <div class="otp-code">{{ $otp }}</div>

            <p>
                This code is valid for <strong>5 minutes</strong>. Do not share
                this code with anyone.
            </p>

            <p class="warning">
                If you did not request this code, please ignore this email and
                ensure your account is secure.
            </p>

            <p>Thank you,<br />{{ config('app.name') }} Team</p>
        </div>
    </body>
</html>
```

---

## `resources/views/emails/temp-password.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Your Temporary Password</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                padding: 40px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .password-code {
                font-size: 24px;
                font-weight: bold;
                color: #2d3748;
                letter-spacing: 4px;
                text-align: center;
                padding: 20px;
                background: #f7fafc;
                border-radius: 8px;
                margin: 20px 0;
            }
            .warning {
                color: #e53e3e;
                font-size: 14px;
                margin-top: 20px;
            }
            h2 {
                color: #2d3748;
            }
            p {
                color: #4a5568;
                line-height: 1.6;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Welcome, {{ $userName }}!</h2>
            <p>
                Your account has been created by an administrator. Please use
                the following temporary password to log in:
            </p>

            <div class="password-code">{{ $tempPassword }}</div>

            <p>
                After logging in, you will be required to
                <strong>change your password immediately</strong>.
            </p>

            <p class="warning">
                Do not share this password with anyone. This is a one-time
                temporary password.
            </p>

            <p>Thank you,<br />{{ config('app.name') }} Team</p>
        </div>
    </body>
</html>
```

---

## `resources/views/emails/password-reset.blade.php`

```html
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Password Reset</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Hello {{ $userName }},</p>
        <p>
            You have requested to reset your password. Use the token below to
            reset your password:
        </p>
        <p
            style="font-size: 18px; font-weight: bold; background-color: #f4f4f4; padding: 10px; display: inline-block;"
        >
            {{ $token }}
        </p>
        <p>This token is valid for <strong>60 minutes</strong>.</p>
        <p>
            If you did not request a password reset, please ignore this email.
        </p>
        <br />
        <p>Regards,<br />{{ config('app.name') }}</p>
    </body>
</html>
```

---

## `resources/views/emails/registration-request.blade.php`

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>New Registration Request</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 500px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 8px;
                padding: 40px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .detail-row {
                padding: 10px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .label {
                font-weight: bold;
                color: #4a5568;
            }
            .value {
                color: #2d3748;
            }
            .notice {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 6px;
                padding: 15px;
                margin-top: 20px;
                font-size: 14px;
                color: #856404;
            }
            h2 {
                color: #2d3748;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>New User Registration Request</h2>
            <p>A user has requested registration approval.</p>

            <div class="detail-row">
                <span class="label">Username:</span>
                <span class="value">{{ $username }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Email:</span>
                <span class="value">{{ $email }}</span>
            </div>

            <div class="detail-row">
                <span class="label">Requested At:</span>
                <span class="value">{{ $requestedAt }}</span>
            </div>

            <div class="notice">
                <strong>Action Required:</strong> Please review this request
                and, if approved, manually create the user account through the
                admin panel.
            </div>

            <br />
            <p>Regards,<br />{{ config('app.name') }}</p>
        </div>
    </body>
</html>
```

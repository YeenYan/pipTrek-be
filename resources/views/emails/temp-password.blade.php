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
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
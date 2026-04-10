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
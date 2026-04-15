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
                <strong>Action Required:</strong> Please review this request and, if approved, manually create the user account through the admin panel.
            </div>

            <br />
            <p>Regards,<br />{{ config('app.name') }}</p>
        </div>
    </body>
</html>

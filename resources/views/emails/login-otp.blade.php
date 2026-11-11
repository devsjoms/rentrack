<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RentTrack Login Verification</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f4f6f8;padding:30px;">
    <div style="max-width:500px;margin:auto;background:white;padding:30px;border-radius:12px;">

        <h2 style="color:#0f766e;">RentTrack</h2>

        <h3>Login Verification</h3>

        <p>Use the following verification code to complete your login:</p>

        <h1 style="text-align:center;letter-spacing:8px;color:#0f766e;">
            {{ $otp }}
        </h1>

        <p>This code will expire in <strong>5 minutes</strong>.</p>

        <p>If you did not attempt to log in, you can ignore this message.</p>

        <hr>

        <small style="color:#888;">
            RentTrack Security System
        </small>

    </div>
</body>
</html>

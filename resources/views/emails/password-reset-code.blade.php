<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Password reset code</title></head>
<body style="font-family:Arial,sans-serif;background:#f1f5f9;margin:0;padding:28px;color:#1f2937">
<table role="presentation" style="max-width:560px;width:100%;margin:auto;background:white;border-radius:12px;padding:28px">
<tr><td>
<h1 style="color:#0f766e;margin:0 0 20px">RentTrack</h1>
<h2>Password reset verification</h2>
<p>Enter this code on the password reset page:</p>
<p style="font-size:32px;font-weight:800;letter-spacing:8px;text-align:center;background:#f0fdfa;padding:20px">{{ $code }}</p>
<p>This code expires in {{ $minutes }} minutes and can only be used once. Never share it with anyone.</p>
<p>If you didn't request a reset, you can ignore this email.</p>
</td></tr></table>
</body></html>

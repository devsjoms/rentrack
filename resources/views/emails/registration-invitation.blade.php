
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete your RentTrack registration</title>
</head>
<body style="margin:0;background:#f1f5f9;color:#1f2937;font-family:Arial,sans-serif;line-height:1.6;padding:28px 16px;">
    <table role="presentation" style="width:100%;max-width:560px;margin:auto;border-spacing:0;background:#ffffff;border-radius:12px;">
        <tr>
            <td style="padding:32px;">
                <h1 style="margin:0 0 20px;color:#0f766e;font-size:26px;">RentTrack</h1>
                <h2 style="margin:0 0 16px;font-size:22px;">Your registration is approved</h2>
                <p>Your tenant registration request has been approved. Complete your account setup using the secure link below.</p>
                <p style="margin:28px 0;">
                    <a href="{{ $invitationUrl }}" style="display:inline-block;background:#0f766e;color:#ffffff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">Complete registration</a>
                </p>
                <p>This one-time invitation expires in <strong>72 hours</strong>. Do not forward this email or share the link.</p>
                <p>If you did not request a RentTrack account, you can safely ignore this message.</p>
                <p style="margin:28px 0 0;color:#64748b;font-size:13px;">RentTrack Management</p>
            </td>
        </tr>
    </table>
</body>
</html>

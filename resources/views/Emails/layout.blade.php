<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Hệ thống đầu tư cá nhân' }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 16px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 18px; color: #1a1a1a; }
        .content { margin-bottom: 32px; }
        .footer { border-top: 1px solid #eee; padding-top: 16px; font-size: 12px; color: #666; }
        .footer a { color: #2563eb; text-decoration: none; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff !important; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Hệ thống đầu tư cá nhân — Invest manager</h1>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p style="margin: 0 0 8px 0;">© 2026 Invest manager. All rights reserved.</p>
            <p style="margin: 0;">👉 Mọi thắc mắc hoặc liên hệ vui lòng gửi về email: <a href="mailto:lehuuphuoc0196@gmail.com">lehuuphuoc0196@gmail.com</a></p>
        </div>
    </div>
</body>
</html>

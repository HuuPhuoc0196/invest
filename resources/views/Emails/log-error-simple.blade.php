<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Log {{ $logLevel }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="background: {{ $levelColor }}; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h2 style="margin: 0;">{{ $levelIcon }} Log {{ $logLevel }}</h2>
    </div>

    <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
        <p style="margin: 0;"><strong>Thời gian:</strong> {{ $logTime }}</p>
        <p style="margin: 10px 0 0 0;"><strong>Ứng dụng:</strong> {{ $appUrl }}</p>
    </div>

    <div style="background: white; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
        <h3 style="margin-top: 0; color: {{ $levelColor }};">Thông điệp:</h3>
        <p style="margin: 0; white-space: pre-wrap;">{{ $logMessage }}</p>
    </div>

    @if(!empty($logContext) && count($logContext) > 0)
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px;">
        <h3 style="margin-top: 0;">Context:</h3>
        <pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 300px;">{{ json_encode($logContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
        <p>Email tự động từ hệ thống logging - {{ config('app.name') }}</p>
    </div>

</body>
</html>

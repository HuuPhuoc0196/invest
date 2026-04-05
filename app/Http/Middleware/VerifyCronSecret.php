<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bảo vệ route chỉ dành cho cron/VPS: yêu cầu secret khớp CRON_API_SECRET.
 * Gửi header: X-Cron-Secret: {secret} hoặc Authorization: Bearer {secret}
 */
class VerifyCronSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.cron.secret');

        if (! is_string($secret) || $secret === '') {
            abort(503, 'Cron API chưa được cấu hình (CRON_API_SECRET).');
        }

        $provided = $request->header('X-Cron-Secret', '')
            ?: (string) ($request->bearerToken() ?? '');

        if ($provided === '' || ! hash_equals($secret, $provided)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}

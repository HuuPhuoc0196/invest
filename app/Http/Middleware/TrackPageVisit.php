<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    private const SKIP_PREFIXES = [
        'api/',
        '__debug/',
        'build/',
        'storage/',
        'admin/',   // admin panel navigation không phải user traffic
    ];

    private const SKIP_EXACT = [
        'robots.txt',
        'sitemap.xml',
        'favicon.ico',
        'logo.svg',
    ];

    private const SKIP_SUFFIXES = [
        '/data',
        '/count',
        '/export/pdf',
        '/export/csv',
        '/lich-su-risk',
        '/co-tuc',
    ];

    // UA patterns của bots, crawlers, monitoring tools — case-insensitive
    private const BOT_UA_PATTERNS = [
        'bot', 'crawler', 'spider', 'slurp', 'scraper',
        'googlebot', 'bingbot', 'baiduspider', 'yandexbot', 'duckduckbot',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'slackbot',
        'whatsapp', 'telegrambot', 'discordbot', 'embedly',
        'semrush', 'ahrefs', 'moz.com', 'majestic',
        'screaming frog', 'sitechecker', 'uptimerobot', 'pingdom', 'datadog',
        'python-requests', 'python-urllib', 'go-http-client',
        'java/', 'okhttp', 'curl/', 'wget/', 'libwww',
        'postman', 'insomnia', 'axios/', 'node-fetch',
        'php/', 'laravel',
    ];

    // Throttle: cùng session + page trong vòng N giây → bỏ qua (chống F5 spam)
    private const THROTTLE_SECONDS = 600; // 10 phút

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Chỉ track GET, không track AJAX
        if ($request->method() !== 'GET' || $request->expectsJson()) {
            return $response;
        }

        $path = ltrim($request->path(), '/');

        // Skip exact paths
        if (in_array($path, self::SKIP_EXACT)) {
            return $response;
        }

        // Skip prefix paths (admin/, api/, ...)
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $response;
            }
        }

        // Skip suffix paths (polling endpoints, exports)
        foreach (self::SKIP_SUFFIXES as $suffix) {
            if (str_ends_with('/' . $path, $suffix)) {
                return $response;
            }
        }

        // --- Bot filter: skip known crawlers & tools ---
        $ua = strtolower($request->userAgent() ?? '');
        if (empty($ua) || $this->isBot($ua)) {
            return $response;
        }

        // --- Session throttle: cùng session + page trong 10 phút → skip ---
        $sessionId = $request->session()->getId();
        $throttleKey = 'pv:' . md5($sessionId . $path);
        if (Cache::has($throttleKey)) {
            return $response;
        }

        try {
            DB::table('page_visits')->insert([
                'user_id'    => Auth::id(),
                'session_id' => $sessionId,
                'page'       => '/' . $path,
                'page_title' => $this->resolveTitle($path),
                'method'     => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 512),
                'visited_at' => now(),
            ]);

            // Đặt throttle sau khi insert thành công
            Cache::put($throttleKey, 1, self::THROTTLE_SECONDS);
        } catch (\Throwable $e) {
            Log::warning('TrackPageVisit failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function isBot(string $lowerUa): bool
    {
        foreach (self::BOT_UA_PATTERNS as $pattern) {
            if (str_contains($lowerUa, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function resolveTitle(string $path): string
    {
        return match(true) {
            $path === 'trang-chu'                    => 'Trang chủ',
            $path === 'gioi-thieu'                   => 'Giới thiệu',
            $path === 'lien-he'                      => 'Liên hệ',
            $path === 'dang-nhap'                    => 'Đăng nhập',
            $path === 'dang-ky'                      => 'Đăng ký',
            $path === 'quen-mat-khau'                => 'Quên mật khẩu',
            str_starts_with($path, 'co-phieu/')      => 'Cổ phiếu: ' . strtoupper(substr($path, 9)),
            $path === 'user/profile'                 => 'Tài sản cá nhân',
            $path === 'user/follow'                  => 'Theo dõi giá',
            $path === 'user/investment-performance'  => 'Hiệu suất đầu tư',
            $path === 'user/buy'                     => 'Mua cổ phiếu',
            $path === 'user/sell'                    => 'Bán cổ phiếu',
            $path === 'user/email-settings'          => 'Cài đặt email',
            str_starts_with($path, 'user/')          => 'User: ' . $path,
            default                                  => '/' . $path,
        };
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Giới hạn số request POST tới đăng nhập / đăng ký / quên mật khẩu / đặt lại mật khẩu theo IP.
 */
class ThrottleAuthPosts
{
    private const PATHS = [
        'dang-nhap',
        'login',
        'dang-ky',
        'register',
        'quen-mat-khau',
        'forgotPassword',
        'dat-lai-mat-khau',
        'reset-password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        if (! in_array($request->path(), self::PATHS, true)) {
            return $next($request);
        }

        $key = 'auth-post:' . $request->path() . ':' . $request->ip();
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retry = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau ' . max(1, (int) ceil($retry)) . ' giây.',
                ], 429);
            }

            return back()
                ->withErrors(['email' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'])
                ->withInput();
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}

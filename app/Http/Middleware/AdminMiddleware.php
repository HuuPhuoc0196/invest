<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Kiểm tra đã đăng nhập
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Cho phép nếu role === 1 (admin), ngược lại thì về /
        if (Auth::user()->role !== 1) {
            return redirect('/');
        }

        return $next($request);
    }
}

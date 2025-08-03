<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Cho phép nếu role === 0 (user), ngược lại thì redirect về /admin
        if (Auth::user()->role !== 0) {
            return redirect('/admin');
        }
        return $next($request);
    }
}

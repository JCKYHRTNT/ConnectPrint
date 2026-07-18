<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Guest
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        // User
        if (session('role') !== 'admin') {
            return redirect()->route('home.user');
        }

        // Admin
        return $next($request);
    }
}

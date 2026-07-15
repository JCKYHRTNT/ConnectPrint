<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            $slug = Str::slug(session('name'));

            return redirect()->route('home.user', [
                'username' => $slug,
            ]);
        }

        // Admin
        return $next($request);
    }
}
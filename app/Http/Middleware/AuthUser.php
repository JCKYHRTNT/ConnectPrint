<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $urlUsername = $request->route('username');

        if ($urlUsername === null) {
            return $next($request);
        }

        $sessionUsername = Str::slug(session('name'));

        if ($urlUsername !== $sessionUsername) {
            return redirect()->route('home.user')->with(
                'error',
                'Not authorized to access another user page.'
            );
        }

        return $next($request);
    }
}

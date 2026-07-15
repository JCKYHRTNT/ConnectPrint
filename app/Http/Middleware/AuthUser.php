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
        if (!session('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        // Username slug in URL
        $urlUsername = $request->route('username');  

        // Session username slug
        $sessionUsername = Str::slug(session('name'));

        // If URL username does NOT match logged-in user → reject
        if ($urlUsername !== $sessionUsername) {

            return redirect("/u/{$sessionUsername}")->with(
                'error',
                'Not authorized to access another user’s page.'
            );
        }

        return $next($request);
    }
}
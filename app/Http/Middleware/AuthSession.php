<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('auth_user')) {
            return redirect()->route('login')->with('status', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}

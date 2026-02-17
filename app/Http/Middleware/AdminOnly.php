<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->session()->get('auth_user', []);

        if (($authUser['role'] ?? null) !== 'admin') {
            return redirect()->route('dashboard')->with('status', 'Halaman ini hanya untuk admin.');
        }

        return $next($request);
    }
}

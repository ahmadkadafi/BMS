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
            $allowedResorId = $authUser['allowed_resor_id'] ?? null;
            if ($allowedResorId) {
                return redirect()->route('dashboard.resor', ['resor' => $allowedResorId])
                    ->with('status', 'Halaman ini hanya untuk admin.');
            }
            return redirect()->route('login')->with('status', 'Halaman ini hanya untuk admin.');
        }

        return $next($request);
    }
}

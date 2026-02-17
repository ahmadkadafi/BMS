<?php

namespace App\Http\Middleware;

use App\Models\Resor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('auth_user')) {
            $authUser = $request->session()->get('auth_user', []);

            if (($authUser['role'] ?? null) === 'admin') {
                return redirect()->route('dashboard');
            }

            if (isset($authUser['allowed_resor_id'])) {
                return redirect()->route('dashboard.resor', ['resor' => $authUser['allowed_resor_id']]);
            }

            $firstResorId = Resor::query()->orderBy('id')->value('id');
            if ($firstResorId) {
                return redirect()->route('dashboard.resor', ['resor' => $firstResorId]);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}

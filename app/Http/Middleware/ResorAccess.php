<?php

namespace App\Http\Middleware;

use App\Support\ResorUserResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = $request->session()->get('auth_user', []);

        if (($authUser['role'] ?? null) === 'admin') {
            return $next($request);
        }

        $allowedResorId = $authUser['allowed_resor_id'] ?? ResorUserResolver::resolveAllowedResorId($authUser['username'] ?? null);

        if (! $allowedResorId && ! ResorUserResolver::isScopedUsername($authUser['username'] ?? null)) {
            return $next($request);
        }

        if (! $allowedResorId) {
            abort(403, 'Resor untuk akun ini tidak ditemukan.');
        }

        $request->session()->put('auth_user.allowed_resor_id', $allowedResorId);
        $routeResor = $request->route('resor');

        if (! $routeResor) {
            return redirect()->route('monitoring.resor', ['resor' => $allowedResorId]);
        }

        $routeResorId = is_object($routeResor) ? $routeResor->id : (int) $routeResor;

        if ((int) $routeResorId !== (int) $allowedResorId) {
            return redirect()->route('monitoring.resor', ['resor' => $allowedResorId]);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IngestToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Node-RED tidak selalu mengirim Accept; pastikan error validasi berupa JSON, bukan redirect
        $request->headers->set('Accept', 'application/json');

        $token = (string) config('services.ingest.token');

        if ($token === '') {
            return response()->json(['message' => 'INGEST_TOKEN belum diisi di .env.'], 503);
        }

        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        }

        return $next($request);
    }
}

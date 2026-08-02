<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicensingApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('licensing.api_token', '');

        if ($configured === '') {
            return response()->json([
                'message' => __('Licensing API is not configured.'),
            ], 503);
        }

        $header = (string) $request->header('Authorization', '');
        $token = null;

        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            $token = $matches[1];
        }

        $token = $token ?: (string) $request->header('X-Licensing-Token', '');

        if ($token === '' || ! hash_equals($configured, $token)) {
            return response()->json([
                'message' => __('Unauthenticated.'),
            ], 401);
        }

        return $next($request);
    }
}

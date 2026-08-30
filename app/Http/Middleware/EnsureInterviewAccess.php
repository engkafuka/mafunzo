<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInterviewAccess
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessInterviewModule()) {
            abort(403);
        }

        if ($roles !== [] && ! $user->hasInterviewRole(...$roles)) {
            abort(403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageExamResults
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canManageExamResults()) {
            abort(403, __('Unauthorized. Only trainers and application management staff can enter exam results.'));
        }

        return $next($request);
    }
}

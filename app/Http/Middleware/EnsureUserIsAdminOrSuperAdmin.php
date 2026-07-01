<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrSuperAdmin
{
    /**
     * Handle an incoming request. Allow only super_admin and admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, ['super_admin', 'admin'], true)) {
            abort(403, 'Unauthorized. Only Super Admin and Admin can access this area.');
        }

        return $next($request);
    }
}

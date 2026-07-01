<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageApplications
{
    /**
     * Allow only super_admin, admin, and staff to access application management.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, ['super_admin', 'admin', 'staff'], true)) {
            abort(403, 'Unauthorized. Only Super Admin, Admin, and Staff can access Application Management.');
        }

        return $next($request);
    }
}

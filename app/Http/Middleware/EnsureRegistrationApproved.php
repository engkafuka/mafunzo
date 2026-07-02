<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'trainee' && ! $user->hasApprovedRegistration()) {
            return redirect()->route('registration.pending');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationPendingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        if ($user->hasApprovedRegistration()) {
            return redirect()->route('dashboard');
        }

        return view('auth.registration-pending', compact('user'));
    }
}

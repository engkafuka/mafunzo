<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $defaultUrl = route('dashboard', absolute: false);

        // Don't send non-trainees to trainee-only routes (e.g. /training) from intended URL
        $intended = $request->session()->get('url.intended');
        if ($intended && $user->role !== 'trainee') {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';
            if (str_starts_with($path, '/training')) {
                $request->session()->forget('url.intended');
                return redirect()->to($defaultUrl);
            }
        }

        if ($user && $user->role === 'trainee' && ! $user->hasCompletedTraineeProfile()) {
            return redirect()->intended(route('trainee.profile.edit', absolute: false));
        }

        return redirect()->intended($defaultUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

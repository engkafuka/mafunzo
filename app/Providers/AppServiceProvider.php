<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(6)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            // Optional: checks Have I Been Pwned over HTTPS. Disable on servers without
            // outbound internet or CA certificates (otherwise registration/user forms may hang → 504).
            if (filter_var(env('PASSWORD_UNCOMPROMISED', false), FILTER_VALIDATE_BOOL)) {
                $rule->uncompromised();
            }

            return $rule;
        });
    }
}

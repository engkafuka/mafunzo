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

            if (! $this->app->environment('local', 'testing')) {
                $rule->uncompromised();
            }

            return $rule;
        });
    }
}

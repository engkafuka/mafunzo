<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class ListReturn
{
    public static function current(): string
    {
        $query = request()->query();
        unset($query['return']);

        $url = request()->url();

        if ($query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }

    public static function attach(string $url): string
    {
        return self::appendReturn($url, self::current());
    }

    public static function url(string $fallback): string
    {
        return self::incomingReturn() ?? $fallback;
    }

    public static function preserve(string $url): string
    {
        $return = self::incomingReturn();

        if ($return === null) {
            return $url;
        }

        return self::appendReturn($url, $return);
    }

    public static function redirect(string $fallback): RedirectResponse
    {
        return redirect()->to(self::url($fallback));
    }

    public static function redirectPreserving(string $url): RedirectResponse
    {
        return redirect()->to(self::preserve($url));
    }

    public static function incomingReturn(): ?string
    {
        $return = request()->input('return') ?? request()->query('return');

        if (! is_string($return) || $return === '') {
            return null;
        }

        return self::isSafe($return) ? $return : null;
    }

    public static function isSafe(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);

        return $appHost && $urlHost && strcasecmp($appHost, $urlHost) === 0;
    }

    private static function appendReturn(string $url, string $return): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'return='.urlencode($return);
    }
}

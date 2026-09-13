@props([
    'href',
])

<a href="{{ \App\Support\ListReturn::attach($href) }}" {{ $attributes }}>
    {{ $slot }}
</a>

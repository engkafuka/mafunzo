@props(['max' => '7xl'])

@php
    $maxClass = match ($max) {
        '4xl' => 'page-inner-4xl',
        '6xl' => 'page-inner-6xl',
        default => 'page-inner-7xl',
    };
@endphp

<div {{ $attributes->merge(['class' => 'page-shell']) }}>
    <div class="{{ $maxClass }}">
        {{ $slot }}
    </div>
</div>

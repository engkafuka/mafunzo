@php
    $logoFile = public_path('images/wrrblogo.png');
    $logoVersion = is_file($logoFile) ? filemtime($logoFile) : time();
@endphp
<img
    src="{{ asset('images/wrrblogo.png') }}?v={{ $logoVersion }}"
    alt="{{ __('Warehouse Receipts Regulatory Board (WRRB)') }}"
    {{ $attributes->merge(['class' => 'object-contain bg-transparent']) }}
/>

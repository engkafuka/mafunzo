<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Certificate') }} — {{ $application->registration_number }}</title>
    <style>
        body { font-family: serif; max-width: 800px; margin: 2rem auto; padding: 2rem; border: 2px solid #1a365d; }
        .header { text-align: center; margin-bottom: 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; color: #1a365d; }
        .title { font-size: 1.75rem; margin-top: 1rem; text-decoration: underline; }
        .body { text-align: center; margin: 2rem 0; line-height: 1.8; }
        .name { font-size: 1.5rem; font-weight: bold; margin: 1rem 0; }
        .meta { font-size: 0.95rem; color: #4a5568; margin-top: 1rem; }
        .footer { margin-top: 3rem; display: flex; justify-content: space-between; }
        .date { font-size: 0.9rem; }
        @media print { body { border: none; margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ config('app.name', 'WRRB') }}</div>
        <div class="title">{{ __('Certificate of Completion') }}</div>
    </div>
    <div class="body">
        <p>{{ __('This is to certify that') }}</p>
        <div class="name">{{ $application->first_name }} {{ $application->middle_name }} {{ $application->last_name }}</div>
        <p>{{ __('has successfully completed the training course') }}</p>
        <p><strong>{{ $application->course->name }}</strong></p>
        <div class="meta">
            {{ __('Registration number') }}: {{ $application->registration_number }}<br>
            {{ __('Date') }}: {{ $application->certificate_issued_at?->format('F j, Y') ?? now()->format('F j, Y') }}
        </div>
    </div>
    <div class="footer">
        <span class="date">{{ now()->format('Y-m-d') }}</span>
        <span class="date">{{ config('app.name') }}</span>
    </div>
    <p class="no-print" style="margin-top: 2rem; text-align: center;">
        <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded cursor-pointer">{{ __('Print certificate') }}</button>
        <a href="{{ route('app-management.certificates') }}" style="margin-left: 1rem; color: #4f46e5;">{{ __('Back to list') }}</a>
    </p>
</body>
</html>

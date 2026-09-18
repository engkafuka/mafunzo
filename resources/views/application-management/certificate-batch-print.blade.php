<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Certificates') }} — {{ $course->name }}</title>
    @include('application-management.partials.certificate-styles')
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="btn-print" onclick="window.print()">
            {{ __('Print :count certificates', ['count' => $certificates->count()]) }}
        </button>
        <a href="{{ route('app-management.certificates', ['course_id' => $course->id]) }}" class="btn-back">
            {{ __('Back to list') }}
        </a>
        <p style="font-family: system-ui, sans-serif; font-size: 0.875rem; color: #475569; margin-top: 0.75rem;">
            {{ __('Course') }}: {{ $course->displayNameWithSession() }}
            · {{ __('Eligible certificates') }}: {{ $certificates->count() }}
        </p>
    </div>

    @foreach($certificates as $certificate)
        @include('application-management.partials.certificate-sheet', $certificate)
    @endforeach
</body>
</html>

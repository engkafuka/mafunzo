<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Certificate') }} — {{ $application->registration_number }}</title>
    @include('application-management.partials.certificate-styles')
    <style>
        @media print {
            .sheet {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="btn-print" onclick="window.print()">{{ __('Print certificate') }}</button>
        <a href="{{ \App\Support\ListReturn::url(route('app-management.certificates')) }}" class="btn-back" style="display:inline-flex;align-items:center;gap:6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            {{ __('Back to list') }}
        </a>
        <p class="print-hint">
            {{ __('If the green background or WRRB watermark is missing on paper, turn on “Background graphics” in the print dialog (Chrome/Edge: More settings).') }}
        </p>
    </div>

    @include('application-management.partials.certificate-sheet', get_defined_vars())
</body>
</html>

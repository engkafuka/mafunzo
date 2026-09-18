<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Applications') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 10px; }
        .filters { margin-bottom: 10px; }
        .filters span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; }
        .empty { text-align: center; color: #666; padding: 16px; }
    </style>
</head>
<body>
    <h1>{{ __('Training applications') }}</h1>
    <p class="meta">
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
        · {{ __('Records') }}: {{ $applications->count() }}
    </p>

    @if(! empty($filters))
        <p class="filters">
            @foreach($filters as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>{{ __('Registration') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Gender') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Phone') }}</th>
                <th>{{ __('Course') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Review') }}</th>
                <th>{{ __('Payment verified') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->registration_number ?? $application->control_number ?? '—' }}</td>
                    <td>{{ trim($application->first_name.' '.($application->middle_name ?? '').' '.$application->last_name) }}</td>
                    <td>{{ $application->gender ? __(ucfirst($application->gender)) : '—' }}</td>
                    <td>{{ $application->email }}</td>
                    <td>{{ $application->phone }}</td>
                    <td>{{ $application->course?->name ?? '—' }}</td>
                    <td>{{ $application->status }}</td>
                    <td>{{ $application->application_review_status }}</td>
                    <td>{{ $application->payment_verified_at ? __('Yes') : __('No') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">{{ __('No applications found.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

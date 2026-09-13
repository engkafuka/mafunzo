<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Attendance records') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 10px; }
        .session { margin-bottom: 12px; font-size: 10px; }
        .session span { display: inline-block; margin-right: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; }
        .empty { text-align: center; color: #666; padding: 16px; }
    </style>
</head>
<body>
    <h1>{{ __('Training attendance') }}</h1>
    <p class="meta">
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
        · {{ __('Records') }}: {{ $records->count() }}
    </p>

    <p class="session">
        <span><strong>{{ __('Course') }}:</strong> {{ $session->course?->name ?? '—' }}</span>
        <span><strong>{{ __('Session') }}:</strong> {{ $session->name }}</span>
        <span><strong>{{ __('Date') }}:</strong> {{ $session->session_date?->format('d M Y') ?? '—' }}</span>
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Registration') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Phone') }}</th>
                <th>{{ __('Company') }}</th>
                <th>{{ __('Scanned at') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                @php($application = $record->trainingApplication)
                <tr>
                    <td>{{ $application?->registration_number ?? '—' }}</td>
                    <td>{{ trim(($application?->first_name ?? '').' '.($application?->last_name ?? '')) ?: '—' }}</td>
                    <td>{{ $application?->email ?? '—' }}</td>
                    <td>{{ $application?->phone ?? '—' }}</td>
                    <td>{{ $application?->company_name ?? '—' }}</td>
                    <td>{{ $record->scanned_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">{{ __('No attendance recorded yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

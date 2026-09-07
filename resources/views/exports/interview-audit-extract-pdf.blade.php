<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Interview audit extract') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .filters { margin-bottom: 10px; }
        .filters span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; }
        .empty { text-align: center; color: #666; padding: 20px; }
    </style>
</head>
<body>
    <h1>{{ __('WRRB operator interview — audit extract') }}</h1>
    <p class="meta">
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
        · {{ __('Records') }}: {{ $rows->count() }}
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
                <th>{{ __('Timestamp') }}</th>
                <th>{{ __('Action') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Session') }}</th>
                <th>{{ __('Company') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $row->action }}</td>
                    <td>{{ $row->description }}</td>
                    <td>{{ $row->user?->name ?? __('System') }}</td>
                    <td>{{ $row->session?->session_code ?? '—' }}</td>
                    <td>{{ $row->session?->company?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">{{ __('No audit records match the selected filters.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

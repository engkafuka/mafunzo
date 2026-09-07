<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Panel attendance') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .filters { margin-bottom: 10px; }
        .filters span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; }
        .empty { text-align: center; color: #666; padding: 20px; }
    </style>
</head>
<body>
    <h1>{{ __('WRRB operator interview — panel attendance') }}</h1>
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
                <th>{{ __('Date') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Company') }}</th>
                <th>{{ __('Interviewee') }}</th>
                <th>{{ __('Panelist') }}</th>
                <th>{{ __('Role') }}</th>
                <th>{{ __('Submission') }}</th>
                <th>{{ __('Submitted at') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->session?->interview_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $row->session?->session_code ?? '—' }}</td>
                    <td>{{ $row->session?->company?->name ?? '—' }}</td>
                    <td>{{ $row->session?->interviewee_name ?? '—' }}</td>
                    <td>
                        {{ $row->user?->name ?? '—' }}
                        @if($row->user?->email)
                            <br><span style="color:#555">{{ $row->user->email }}</span>
                        @endif
                    </td>
                    <td>{{ $row->is_chair ? __('Chair') : __('Panelist') }}</td>
                    <td>{{ \App\Support\Interview\InterviewPanelAttendanceReport::submissionLabel($row->submission_status) }}</td>
                    <td>{{ $row->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">{{ __('No panel attendance records match the selected filters.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

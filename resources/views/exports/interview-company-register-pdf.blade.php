<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Interview company register') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .filters { margin-bottom: 10px; }
        .filters span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; }
        .pass { color: #166534; font-weight: bold; }
        .fail { color: #991b1b; font-weight: bold; }
        .empty { text-align: center; color: #666; padding: 20px; }
    </style>
</head>
<body>
    <h1>{{ __('WRRB operator interview — company register') }}</h1>
    <p class="meta">
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
        · {{ __('Records') }}: {{ $sessions->count() }}
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
                <th>{{ __('Reg. no.') }}</th>
                <th>{{ __('Interviewee') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Score %') }}</th>
                <th>{{ __('Pass') }}</th>
                <th>{{ __('Recommendation') }}</th>
                <th>{{ __('Decision') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $session)
                <tr>
                    <td>{{ $session->interview_date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $session->session_code }}</td>
                    <td>{{ $session->company?->name ?? '—' }}</td>
                    <td>{{ $session->company?->registration_number ?? '—' }}</td>
                    <td>
                        {{ $session->interviewee_name }}
                        @if($session->interviewee_title)
                            <br><span style="color:#555">{{ $session->interviewee_title }}</span>
                        @endif
                    </td>
                    <td>{{ $session->typeLabel() }}</td>
                    <td>{{ $session->statusLabel() }}</td>
                    <td>
                        {{ $session->result?->percentage !== null
                            ? number_format((float) $session->result->percentage, 2)
                            : '—' }}
                    </td>
                    <td @class([
                        'pass' => $session->result?->passed === true,
                        'fail' => $session->result?->passed === false,
                    ])>
                        {{ \App\Support\Interview\InterviewCompanyReport::passLabel($session) }}
                    </td>
                    <td>{{ \App\Support\Interview\InterviewCompanyReport::recommendationLabel($session->result?->recommendation) }}</td>
                    <td>{{ \App\Support\Interview\InterviewCompanyReport::decisionLabel($session->result?->decision_status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty">{{ __('No interviews match the selected filters.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

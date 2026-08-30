<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Interview score sheet') }} — {{ $session->session_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .pass { color: #166534; font-weight: bold; }
        .fail { color: #991b1b; font-weight: bold; }
        .summary { margin-bottom: 16px; }
        .summary td { border: none; padding: 2px 8px 2px 0; }
    </style>
</head>
<body>
    <h1>{{ __('WRRB operator interview — consolidated score sheet') }}</h1>
    <p class="meta">
        {{ __('Session') }}: {{ $session->session_code }}<br>
        {{ __('Company') }}: {{ $session->company->name }}<br>
        {{ __('Interviewee') }}: {{ $session->interviewee_name }} @if($session->interviewee_title)({{ $session->interviewee_title }})@endif<br>
        {{ __('Date') }}: {{ $session->interview_date?->format('Y-m-d') ?? '—' }} · {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
    </p>

    @if($session->result)
        <table class="summary">
            <tr><td><strong>{{ __('Total score') }}</strong></td><td>{{ $session->result->total_score }} / {{ $session->result->max_possible_score }}</td></tr>
            <tr><td><strong>{{ __('Percentage') }}</strong></td><td>{{ $session->result->percentage }}%</td></tr>
            <tr><td><strong>{{ __('Pass mark') }}</strong></td><td>{{ $session->pass_mark }}%</td></tr>
            <tr><td><strong>{{ __('Outcome') }}</strong></td><td class="{{ $session->result->passed ? 'pass' : 'fail' }}">{{ $session->result->passed ? __('Pass') : __('Fail') }}</td></tr>
            <tr><td><strong>{{ __('Recommendation') }}</strong></td><td>{{ str_replace('_', ' ', $session->result->recommendation ?? '—') }}</td></tr>
            @if($session->result->chair_notes)
                <tr><td><strong>{{ __('Chair notes') }}</strong></td><td>{{ $session->result->chair_notes }}</td></tr>
            @endif
        </table>
    @endif

    <table>
        <thead>
            <tr>
                <th>{{ __('Question') }}</th>
                <th>{{ __('Max') }}</th>
                <th>{{ __('Average') }}</th>
                <th>{{ __('Panelist scores') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($questions as $row)
                <tr>
                    <td>{{ $row['question_text'] ?? '' }}</td>
                    <td>{{ $row['max_mark'] ?? '' }}</td>
                    <td>{{ $row['average_score'] ?? '' }}</td>
                    <td>
                        @foreach($row['panelist_scores'] ?? [] as $ps)
                            {{ $ps['panelist'] ?? '' }}: {{ $ps['score'] ?? '' }}@if(!$loop->last); @endif
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($varianceFlags))
        <p><strong>{{ __('Variance flags') }}:</strong> {{ count($varianceFlags) }} {{ __('question(s) with panelist spread above threshold') }}.</p>
    @endif
</body>
</html>

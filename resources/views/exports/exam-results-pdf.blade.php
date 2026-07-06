<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Examination results') }} — {{ $course->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .pass { color: #166534; font-weight: bold; }
        .fail { color: #991b1b; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ __('Examination results') }}</h1>
    <p class="meta">
        {{ $course->name }} · {{ __('Session') }} {{ $course->session_year }}<br>
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Registration') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Score') }}</th>
                <th>{{ __('Result') }}</th>
                <th>{{ __('Published') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>{{ $application->registration_number }}</td>
                    <td>{{ trim($application->first_name.' '.($application->middle_name ?? '').' '.$application->last_name) }}</td>
                    <td>{{ $application->email }}</td>
                    <td>{{ $application->exam_score !== null ? number_format((float) $application->exam_score, 2).'%' : '—' }}</td>
                    <td @class(['pass' => $application->exam_passed === true, 'fail' => $application->exam_passed === false])>
                        {{ $application->examResultStatusLabel() }}
                    </td>
                    <td>{{ $application->exam_results_published_at ? __('Yes') : __('No') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('No recorded examination results for this course.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

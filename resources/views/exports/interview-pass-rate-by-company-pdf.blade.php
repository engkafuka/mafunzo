<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Pass rate by company') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        .filters { margin-bottom: 10px; }
        .filters span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; }
        td.num, th.num { text-align: right; }
        .empty { text-align: center; color: #666; padding: 20px; }
        .totals td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <h1>{{ __('WRRB operator interview — pass rate by company') }}</h1>
    <p class="meta">
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
        · {{ __('Companies') }}: {{ $rows->count() }}
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
                <th>{{ __('Company') }}</th>
                <th>{{ __('Reg. no.') }}</th>
                <th class="num">{{ __('Sessions') }}</th>
                <th class="num">{{ __('Passed') }}</th>
                <th class="num">{{ __('Failed') }}</th>
                <th class="num">{{ __('No result') }}</th>
                <th class="num">{{ __('Pass rate %') }}</th>
                <th class="num">{{ __('Avg score %') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->company_name ?? '—' }}</td>
                    <td>{{ $row->registration_number ?? '—' }}</td>
                    <td class="num">{{ $row->sessions_count }}</td>
                    <td class="num">{{ $row->passed_count }}</td>
                    <td class="num">{{ $row->failed_count }}</td>
                    <td class="num">{{ $row->pending_count }}</td>
                    <td class="num">{{ $row->pass_rate !== null ? number_format($row->pass_rate, 1) : '—' }}</td>
                    <td class="num">{{ $row->avg_percentage !== null ? number_format($row->avg_percentage, 2) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">{{ __('No company interview data matches the selected filters.') }}</td></tr>
            @endforelse
            @if($rows->isNotEmpty())
                <tr class="totals">
                    <td colspan="2">{{ __('Total') }}</td>
                    <td class="num">{{ $totals['sessions'] }}</td>
                    <td class="num">{{ $totals['passed'] }}</td>
                    <td class="num">{{ $totals['failed'] }}</td>
                    <td class="num">{{ $totals['pending'] }}</td>
                    <td class="num">
                        @php
                            $scored = $totals['passed'] + $totals['failed'];
                            $overall = $scored > 0 ? round(($totals['passed'] / $scored) * 100, 1) : null;
                        @endphp
                        {{ $overall !== null ? number_format($overall, 1) : '—' }}
                    </td>
                    <td class="num">—</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pass rate by company') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl space-y-4">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-back-link :href="route('interview.reports.index')" class="text-sm text-[#0a71ab] hover:text-[#085a89] hover:underline">
                    {{ __('Back to reports') }}
                </x-back-link>
                <p class="text-sm text-gray-500">{{ __(':count companies', ['count' => $rows->count()]) }}</p>
            </div>

            <form method="GET" action="{{ route('interview.reports.pass-rate') }}" class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-3 sm:p-4 space-y-3">
                <div class="filter-bar flex flex-wrap gap-2">
                    <input type="date" name="from_date" value="{{ request('from_date') }}" title="{{ __('From date') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                    <input type="date" name="to_date" value="{{ request('to_date') }}" title="{{ __('To date') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">

                    <select name="company_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="conducted" @selected(request('status', 'conducted') === 'conducted')>{{ __('Conducted') }}</option>
                        <option value="" @selected(request()->has('status') && request('status') === '')>{{ __('All statuses') }}</option>
                        @foreach($sessionStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>

                    <select name="interview_type" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All types') }}</option>
                        @foreach($interviewTypes as $key => $label)
                            <option value="{{ $key }}" @selected(request('interview_type') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('interview.reports.pass-rate.export.csv', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export Excel (CSV)') }}</a>
                    <a href="{{ route('interview.reports.pass-rate.export.pdf', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export PDF') }}</a>
                    <a href="{{ route('interview.reports.pass-rate') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm text-gray-500 hover:text-gray-700">{{ __('Clear filters') }}</a>
                </div>
            </form>

            @php
                $totalSessions = $rows->sum('sessions_count');
                $totalPassed = $rows->sum('passed_count');
                $totalFailed = $rows->sum('failed_count');
                $totalPending = $rows->sum('pending_count');
                $scored = $totalPassed + $totalFailed;
                $overallRate = $scored > 0 ? round(($totalPassed / $scored) * 100, 1) : null;
            @endphp

            <div class="grid gap-3 sm:grid-cols-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">{{ __('Sessions') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalSessions }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">{{ __('Passed') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-green-700">{{ $totalPassed }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">{{ __('Failed') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-red-700">{{ $totalFailed }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500">{{ __('Overall pass rate') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $overallRate !== null ? $overallRate.'%' : '—' }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Sessions') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Passed') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Failed') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('No result') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Pass rate %') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Avg score %') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm">
                                    {{ $row->company_name ?? '—' }}
                                    @if($row->registration_number)
                                        <span class="block text-xs text-gray-500">{{ $row->registration_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-right">{{ $row->sessions_count }}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-700">{{ $row->passed_count }}</td>
                                <td class="px-4 py-3 text-sm text-right text-red-700">{{ $row->failed_count }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ $row->pending_count }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium">{{ $row->pass_rate !== null ? number_format($row->pass_rate, 1) : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-right">{{ $row->avg_percentage !== null ? number_format($row->avg_percentage, 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No company interview data matches the selected filters.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold">{{ __('Total') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">{{ $totalSessions }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">{{ $totalPassed }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">{{ $totalFailed }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">{{ $totalPending }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">{{ $overallRate !== null ? number_format($overallRate, 1) : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-right">—</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

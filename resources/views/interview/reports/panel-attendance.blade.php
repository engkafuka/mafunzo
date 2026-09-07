<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Panel attendance') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl space-y-4">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-back-link :href="route('interview.reports.index')" class="text-sm text-[#0a71ab] hover:text-[#085a89] hover:underline">
                    {{ __('Back to reports') }}
                </x-back-link>
                <p class="text-sm text-gray-500">{{ __(':count matching', ['count' => $rows->total()]) }}</p>
            </div>

            <form method="GET" action="{{ route('interview.reports.panel-attendance') }}" class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-3 sm:p-4 space-y-3">
                <div class="filter-bar flex flex-wrap gap-2">
                    <input type="date" name="from_date" value="{{ request('from_date') }}" title="{{ __('From date') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                    <input type="date" name="to_date" value="{{ request('to_date') }}" title="{{ __('To date') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">

                    <select name="company_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>

                    <select name="session_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All sessions') }}</option>
                        @foreach($sessionOptions as $session)
                            <option value="{{ $session->id }}" @selected((string) request('session_id') === (string) $session->id)>
                                {{ $session->session_code }} — {{ $session->company?->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="conducted" @selected(request('status', 'conducted') === 'conducted')>{{ __('Conducted') }}</option>
                        <option value="" @selected(request()->has('status') && request('status') === '')>{{ __('All statuses') }}</option>
                        @foreach($sessionStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>

                    <select name="submission_status" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All submissions') }}</option>
                        <option value="submitted" @selected(request('submission_status') === 'submitted')>{{ __('Submitted') }}</option>
                        <option value="pending" @selected(request('submission_status') === 'pending')>{{ __('Pending') }}</option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('interview.reports.panel-attendance.export.pdf', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export PDF') }}</a>
                    <a href="{{ route('interview.reports.panel-attendance.export.csv', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export Excel (CSV)') }}</a>
                    <a href="{{ route('interview.reports.panel-attendance') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm text-gray-500 hover:text-gray-700">{{ __('Reset to today') }}</a>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Session') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Panelist') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Role') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Submission') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Submitted at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $row->session?->interview_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $row->session?->session_code ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->session?->company?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $row->user?->name ?? '—' }}
                                    @if($row->user?->email)
                                        <span class="block text-xs text-gray-500">{{ $row->user->email }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $row->is_chair ? __('Chair') : __('Panelist') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($row->submission_status === 'submitted')
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Submitted') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ __('Pending') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $row->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No panel attendance records match the selected filters.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

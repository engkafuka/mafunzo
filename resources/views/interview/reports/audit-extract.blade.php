<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Audit extract') }}</h2>
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

            <form method="GET" action="{{ route('interview.reports.audit-extract') }}" class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-3 sm:p-4 space-y-3">
                <div class="filter-bar flex flex-wrap gap-2">
                    <input type="date" name="from_date" value="{{ request('from_date') }}" title="{{ __('From date') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                    <input type="date" name="to_date" value="{{ request('to_date') }}" title="{{ __('To date') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">

                    <select name="session_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All sessions') }}</option>
                        @foreach($sessionOptions as $session)
                            <option value="{{ $session->id }}" @selected((string) request('session_id') === (string) $session->id)>
                                {{ $session->session_code }}
                            </option>
                        @endforeach
                    </select>

                    <select name="action" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All actions') }}</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>

                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search…') }}" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('interview.reports.audit-extract.export.csv', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export Excel (CSV)') }}</a>
                    <a href="{{ route('interview.reports.audit-extract.export.pdf', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export PDF') }}</a>
                    <a href="{{ route('interview.reports.audit-extract') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm text-gray-500 hover:text-gray-700">{{ __('Reset to today') }}</a>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Timestamp') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Action') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Description') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('User') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Session') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">{{ $row->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-mono text-xs">{{ $row->action }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->description }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->user?->name ?? __('System') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->session?->session_code ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->session?->company?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No audit records match the selected filters.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

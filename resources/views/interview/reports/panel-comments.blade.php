<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Panel comments') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl space-y-4">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-back-link :href="route('interview.reports.index')" class="text-sm text-[#0a71ab] hover:text-[#085a89] hover:underline">
                    {{ __('Back to reports') }}
                </x-back-link>
                @if(isset($rows))
                    <p class="text-sm text-gray-500">{{ __(':count matching', ['count' => $rows->total()]) }}</p>
                @elseif(isset($sessions))
                    <p class="text-sm text-gray-500">{{ __(':count sessions', ['count' => $sessions->total()]) }}</p>
                @endif
            </div>

            @php
                $baseQuery = request()->except(['view', 'page']);
                $tabLink = fn (string $mode) => route('interview.reports.panel-comments', array_merge($baseQuery, ['view' => $mode]));
            @endphp

            <form method="GET" action="{{ route('interview.reports.panel-comments') }}" class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-3 sm:p-4 space-y-3">
                <input type="hidden" name="view" value="{{ $viewMode }}">

                <div class="filter-bar flex flex-wrap gap-2">
                    <input type="date" name="from_date" value="{{ request('from_date') }}" title="{{ __('From date') }}" class="rounded-md border-gray-300 text-sm">
                    <input type="date" name="to_date" value="{{ request('to_date') }}" title="{{ __('To date') }}" class="rounded-md border-gray-300 text-sm">

                    <select name="company_id" class="rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>

                    <select name="session_id" class="rounded-md border-gray-300 text-sm min-w-[220px]">
                        <option value="">{{ __('All sessions') }}</option>
                        @foreach($sessionOptions as $sessionOption)
                            <option value="{{ $sessionOption->id }}" @selected((string) request('session_id') === (string) $sessionOption->id)>
                                {{ $sessionOption->session_code }} — {{ $sessionOption->company?->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="panelist_user_id" class="rounded-md border-gray-300 text-sm min-w-[180px]">
                        <option value="">{{ __('All panelists') }}</option>
                        @foreach($panelistOptions as $panelist)
                            <option value="{{ $panelist->id }}" @selected((string) request('panelist_user_id') === (string) $panelist->id)>{{ $panelist->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-md border-gray-300 text-sm">
                        <option value="conducted" @selected(request('status', 'conducted') === 'conducted')>{{ __('Conducted') }}</option>
                        <option value="" @selected(request()->has('status') && request('status') === '')>{{ __('All statuses') }}</option>
                        @foreach($sessionStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="comments_only" value="0">
                        <input type="checkbox" name="comments_only" value="1" @checked($commentsOnly) class="rounded border-gray-300 text-indigo-600">
                        {{ __('Comments only') }}
                    </label>
                    <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
                </div>

                <div class="flex flex-wrap gap-2 pt-1 border-t border-gray-100">
                    <a href="{{ route('interview.reports.panel-comments.export.pdf', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export PDF') }}</a>
                    <a href="{{ route('interview.reports.panel-comments.export.csv', request()->query()) }}" class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">{{ __('Export Excel (CSV)') }}</a>
                    <a href="{{ route('interview.reports.panel-comments') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm text-gray-500 hover:text-gray-700">{{ __('Reset to today') }}</a>
                </div>
            </form>

            <div class="flex flex-wrap gap-2">
                <a href="{{ $tabLink('table') }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium {{ $viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    {{ __('Table') }}
                </a>
                <a href="{{ $tabLink('grouped') }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium {{ $viewMode === 'grouped' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    {{ __('By session') }}
                </a>
                <a href="{{ $tabLink('session') }}"
                   class="px-3 py-1.5 rounded-md text-sm font-medium {{ $viewMode === 'session' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    {{ __('Session detail') }}
                </a>
            </div>

            @if($viewMode === 'session')
                @include('interview.reports._panel-comments-session')
            @elseif($viewMode === 'grouped')
                @include('interview.reports._panel-comments-grouped')
            @else
                @include('interview.reports._panel-comments-table')
            @endif
        </div>
    </div>
</x-app-layout>

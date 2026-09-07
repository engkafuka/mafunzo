<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Interview sessions') }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts') @include('interview._nav')
        @if(Auth::user()->hasInterviewRole('admin'))
            <div class="mb-4"><a href="{{ route('interview.sessions.create') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase">{{ __('Schedule session') }}</a></div>
        @endif

        <form method="GET" action="{{ route('interview.sessions.index') }}" class="mb-4 filter-bar items-stretch sm:items-center">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('All Status') }}</option>
                @foreach($sessionStatuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ __($label) }}</option>
                @endforeach
            </select>

            <select name="company_id" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('All companies') }}</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) ($filters['company_id'] ?? '') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>

            <select name="interview_type" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('All types') }}</option>
                @foreach($interviewTypes as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['interview_type'] ?? '') === $key)>{{ __($label) }}</option>
                @endforeach
            </select>

            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" title="{{ __('From date') }}" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" title="{{ __('To date') }}" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">

            <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="{{ __('Search code, company, interviewee...') }}"
                       class="w-full pl-9 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
            @if(request()->hasAny(['q', 'status', 'company_id', 'interview_type', 'from_date', 'to_date']))
                <a href="{{ route('interview.sessions.index') }}" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 text-center">{{ __('Clear') }}</a>
            @endif
        </form>

        <div class="bg-white shadow-sm sm:rounded-lg">
            <x-responsive-table>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Code') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Interviewee') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Date') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Status') }}</th>
                    <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($sessions as $session)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $session->session_code }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->company->name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->interviewee_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->interview_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->statusLabel() }}</td>
                        <td class="px-4 py-3 text-right text-sm"><a href="{{ route('interview.sessions.show', $session) }}" class="text-indigo-600">{{ __('View') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No sessions match the selected filters.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            </x-responsive-table>
            <div class="p-4 pagination-responsive">{{ $sessions->links() }}</div>
        </div>
    </div></div>
</x-app-layout>

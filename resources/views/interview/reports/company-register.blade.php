<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Company interview register') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl space-y-4">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-back-link :href="route('interview.reports.index')" class="text-sm text-[#0a71ab] hover:text-[#085a89] hover:underline">
                    {{ __('Back to reports') }}
                </x-back-link>
                <p class="text-sm text-gray-500">{{ __(':count matching', ['count' => $sessions->total()]) }}</p>
            </div>

            <form method="GET"
                  action="{{ route('interview.reports.company-register') }}"
                  id="interview-company-register-form"
                  class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-3 sm:p-4 space-y-3">
                <div class="filter-bar flex flex-wrap gap-2">
                    <input type="date"
                           name="from_date"
                           value="{{ request('from_date') }}"
                           title="{{ __('From date') }}"
                           class="rounded-md border-gray-300 text-sm w-full sm:w-auto"
                           onchange="this.form.requestSubmit()">

                    <input type="date"
                           name="to_date"
                           value="{{ request('to_date') }}"
                           title="{{ __('To date') }}"
                           class="rounded-md border-gray-300 text-sm w-full sm:w-auto"
                           onchange="this.form.requestSubmit()">

                    <select name="company_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>
                                {{ $company->name }}
                                @if($company->registration_number)
                                    ({{ $company->registration_number }})
                                @endif
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

                    <select name="interview_type" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All types') }}</option>
                        @foreach($interviewTypes as $key => $label)
                            <option value="{{ $key }}" @selected(request('interview_type') === $key)>{{ __($label) }}</option>
                        @endforeach
                    </select>

                    <select name="passed" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('Pass / Fail') }}</option>
                        <option value="yes" @selected(request('passed') === 'yes')>{{ __('Pass') }}</option>
                        <option value="no" @selected(request('passed') === 'no')>{{ __('Fail') }}</option>
                        <option value="pending" @selected(request('passed') === 'pending')>{{ __('No result yet') }}</option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('interview.reports.company-register.export.csv', request()->query()) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Export Excel (CSV)') }}
                    </a>
                    <a href="{{ route('interview.reports.company-register.export.pdf', request()->query()) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                        {{ __('Export PDF') }}
                    </a>
                    <a href="{{ route('interview.reports.company-register') }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-md text-sm text-gray-500 hover:text-gray-700">
                        {{ __('Reset to today') }}
                    </a>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Code') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Interviewee') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Type') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Score %') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Pass') }}</th>
                            <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Decision') }}</th>
                            <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sessions as $session)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $session->interview_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $session->session_code }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $session->company?->name ?? '—' }}
                                    @if($session->company?->registration_number)
                                        <span class="block text-xs text-gray-500">{{ $session->company->registration_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $session->interviewee_name }}
                                    @if($session->interviewee_title)
                                        <span class="block text-xs text-gray-500">{{ $session->interviewee_title }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $session->typeLabel() }}</td>
                                <td class="px-4 py-3 text-sm">{{ $session->statusLabel() }}</td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $session->result?->percentage !== null
                                        ? number_format((float) $session->result->percentage, 2)
                                        : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($session->result?->passed === true)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Pass') }}</span>
                                    @elseif($session->result?->passed === false)
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">{{ __('Fail') }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('—') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ \App\Support\Interview\InterviewCompanyReport::decisionLabel($session->result?->decision_status) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('interview.sessions.show', $session) }}" class="text-indigo-600 hover:underline">{{ __('View') }}</a>
                                    @if($session->result)
                                        <a href="{{ route('interview.review.show', $session) }}" class="ml-2 text-indigo-600 hover:underline">{{ __('Review') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">
                                    {{ __('No interviews match the selected filters.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $sessions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

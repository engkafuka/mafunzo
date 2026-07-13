<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trained users report') }}
        </h2>
    </x-slot>

    @php
        $groups = [
            'personal' => __('Personal'),
            'employment' => __('Employment'),
            'training' => __('Training'),
            'outputs' => __('Outputs'),
        ];
        $activeFilters = collect([
            'course_id' => request('course_id'),
            'session_year' => request('session_year'),
            'gender' => request('gender'),
            'region' => request('region'),
            'certificate_status' => request('certificate_status'),
            'id_card_status' => request('id_card_status'),
            'from_date' => request('from_date'),
            'to_date' => request('to_date'),
        ])->filter(fn ($v) => filled($v))->count();
    @endphp

    <div class="page-shell">
        <div class="page-inner-7xl space-y-4"
             x-data="{ columnsOpen: false }">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('app-management.reports.index') }}" class="text-[#0a71ab] hover:underline text-sm">&larr; {{ __('Back to reports') }}</a>
                <p class="text-sm text-gray-500">{{ __(':count matching', ['count' => $applications->total()]) }}</p>
            </div>

            <form method="GET"
                  action="{{ route('app-management.reports.trained-users') }}"
                  id="trained-users-report-form"
                  class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-3 sm:p-4 space-y-3">
                <div class="filter-bar">
                    <select name="course_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All courses') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>
                                {{ $course->name }} @if($course->session_year)({{ $course->session_year }})@endif
                            </option>
                        @endforeach
                    </select>

                    <select name="session_year" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All years') }}</option>
                        @foreach($sessionYears as $year)
                            <option value="{{ $year }}" @selected(request('session_year') == $year)>{{ $year }}</option>
                        @endforeach
                    </select>

                    <select name="gender" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All genders') }}</option>
                        <option value="male" @selected(request('gender') === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected(request('gender') === 'female')>{{ __('Female') }}</option>
                        <option value="other" @selected(request('gender') === 'other')>{{ __('Other') }}</option>
                    </select>

                    <select name="region" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('All regions') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
                        @endforeach
                    </select>

                    <select name="certificate_status" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('Certificate') }}</option>
                        <option value="issued" @selected(request('certificate_status') === 'issued')>{{ __('Issued') }}</option>
                        <option value="not_issued" @selected(request('certificate_status') === 'not_issued')>{{ __('Not issued') }}</option>
                    </select>

                    <select name="id_card_status" class="rounded-md border-gray-300 text-sm" onchange="this.form.requestSubmit()">
                        <option value="">{{ __('ID card') }}</option>
                        <option value="none" @selected(request('id_card_status') === 'none')>{{ __('None') }}</option>
                        <option value="draft" @selected(request('id_card_status') === 'draft')>{{ __('Draft') }}</option>
                        <option value="published" @selected(request('id_card_status') === 'published')>{{ __('Published') }}</option>
                        <option value="revoked" @selected(request('id_card_status') === 'revoked')>{{ __('Revoked') }}</option>
                    </select>

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

                    <div class="relative">
                        <button type="button"
                                @click="columnsOpen = !columnsOpen"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
                            {{ __('Columns') }}
                            <span class="text-xs text-gray-500">({{ count($columns) }})</span>
                            <svg class="h-4 w-4 text-gray-400 transition" :class="columnsOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="columnsOpen"
                             x-cloak
                             @click.outside="columnsOpen = false"
                             class="absolute left-0 z-20 mt-2 w-[min(100vw-2rem,28rem)] rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-gray-800">{{ __('Export columns') }}</span>
                                <div class="flex gap-2 text-xs">
                                    <button type="button"
                                            class="text-[#0a71ab] hover:underline"
                                            onclick="document.querySelectorAll('#trained-users-report-form input[name=\'columns[]\']').forEach(el => el.checked = true)">{{ __('All') }}</button>
                                    <button type="button"
                                            class="text-[#0a71ab] hover:underline"
                                            onclick="document.querySelectorAll('#trained-users-report-form input[name=\'columns[]\']').forEach(el => el.checked = el.dataset.default === '1')">{{ __('Defaults') }}</button>
                                </div>
                            </div>
                            <div class="grid max-h-64 gap-3 overflow-y-auto sm:grid-cols-2">
                                @foreach($groups as $groupKey => $groupLabel)
                                    <div>
                                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $groupLabel }}</p>
                                        <div class="space-y-1">
                                            @foreach($availableColumns as $key => $column)
                                                @if($column['group'] === $groupKey)
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox"
                                                               name="columns[]"
                                                               value="{{ $key }}"
                                                               data-default="{{ $column['default'] ? '1' : '0' }}"
                                                               class="rounded border-gray-300 text-[#0a71ab] focus:ring-[#0a71ab]"
                                                               @checked(in_array($key, $columns, true))>
                                                        <span>{{ $column['label'] }}</span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex justify-end border-t border-gray-100 pt-2">
                                <button type="submit" class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800">{{ __('Apply columns') }}</button>
                            </div>
                        </div>
                    </div>

                    @if($activeFilters > 0)
                        <a href="{{ route('app-management.reports.trained-users') }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                            {{ __('Reset') }} @if($activeFilters)({{ $activeFilters }})@endif
                        </a>
                    @endif

                    <button type="submit"
                            formaction="{{ route('app-management.reports.trained-users.export') }}"
                            class="px-3 py-1.5 bg-[#0a71ab] text-white text-sm rounded-md hover:bg-[#086090] sm:ml-auto">
                        {{ __('Download Excel') }}
                    </button>
                </div>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <x-responsive-table>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach($columns as $key)
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $availableColumns[$key]['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($applications as $application)
                                <tr>
                                    @foreach($columns as $key)
                                        <td class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">
                                            {{ \App\Support\TrainedUsersReport::value($application, $key) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ max(count($columns), 1) }}" class="px-4 py-8 text-center text-gray-500">
                                        {{ __('No trained users match the selected filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-responsive-table>
                <x-table-pagination :paginator="$applications" />
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Interview reports') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @include('interview._alerts')
            @include('interview._nav')

            <p class="mb-6 text-sm text-gray-600">{{ __('Generate and download reports for warehouse operator interviews.') }}</p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('interview.reports.company-register') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Company interview register') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('List interviewed companies with scores, pass/fail, and decisions. Export as PDF or Excel (CSV).') }}</p>
                </a>

                <a href="{{ route('interview.reports.panel-attendance') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Panel attendance') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Panel register with chair/panelist roles and score submission status. Export as PDF or Excel (CSV).') }}</p>
                </a>

                <a href="{{ route('interview.reports.audit-extract') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Audit extract') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Export interview audit trail by date, session, or action. Available as PDF or Excel (CSV).') }}</p>
                </a>

                <a href="{{ route('interview.reports.pass-rate') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Pass rate by company') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Company-level pass/fail counts, pass rate, and average score. Export as PDF or Excel (CSV).') }}</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

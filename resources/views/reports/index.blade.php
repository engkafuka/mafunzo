<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <p class="mb-6 text-sm text-gray-600">{{ __('Generate and download operational reports for WRRB training.') }}</p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('app-management.reports.trained-users') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Trained users report') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Export registered details for trainees who passed training. Filter rows and choose columns, including gender.') }}</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

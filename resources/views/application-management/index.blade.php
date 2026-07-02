<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Application Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('app-management.registrations.index') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Registration verification') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Review and approve new trainee registrations.') }}</p>
                    @if($stats['pending_registrations'] > 0)
                        <p class="mt-2 text-amber-600 font-medium">{{ $stats['pending_registrations'] }} {{ __('pending') }}</p>
                    @endif
                </a>
                <a href="{{ route('app-management.applications') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Review Applications') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Review, approve or reject training applications.') }}</p>
                    @if($stats['pending_review'] > 0)
                        <p class="mt-2 text-indigo-600 font-medium">{{ $stats['pending_review'] }} {{ __('pending') }}</p>
                    @endif
                </a>
                <a href="{{ route('app-management.applications', ['status_filter' => 'pending_account']) }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Verify Accounts') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Verify trainee accounts.') }}</p>
                    @if($stats['pending_account_verify'] > 0)
                        <p class="mt-2 text-amber-600 font-medium">{{ $stats['pending_account_verify'] }} {{ __('pending') }}</p>
                    @endif
                </a>
                <a href="{{ route('app-management.applications', ['status_filter' => 'pending_payment']) }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Review Payments') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Verify payment completion.') }}</p>
                </a>
                <a href="{{ route('app-management.attendance') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Attendance (QR)') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Create sessions and record attendance via QR code.') }}</p>
                </a>
                <a href="{{ route('app-management.exam-results') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Exam Results') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Upload examination scores and pass/fail.') }}</p>
                </a>
                <a href="{{ route('app-management.certificates') }}" class="block p-6 bg-white rounded-lg shadow hover:shadow-md border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Certificates') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Generate and issue certificates.') }}</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

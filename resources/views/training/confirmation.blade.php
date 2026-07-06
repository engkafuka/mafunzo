<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registration Complete') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg mb-6">
                        <p class="text-green-800 font-medium">{{ __('Your payment has been confirmed. Your trainee registration number is:') }}</p>
                        <p class="mt-2 text-2xl font-mono font-bold text-green-700">{{ $application->registration_number }}</p>
                    </div>

                    <p class="text-gray-600">
                        {{ __('Please save this registration number. You will need it for your training.') }}
                    </p>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-500">{{ __('Course') }}: {{ $application->course->name }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ __('Name') }}: {{ $application->first_name }} {{ $application->middle_name }} {{ $application->last_name }}</p>
                    </div>

                    <p class="mt-6">
                        <a href="{{ route('training.my-applications') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            {{ __('View my applications') }} &rarr;
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment') }} — {{ $application->course->name }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-gray-600 mb-6">{{ __('Use the control number below to complete your payment.') }}</p>

                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm font-medium text-gray-700">{{ __('Control Number') }}</p>
                        <p class="mt-1 text-2xl font-mono font-bold text-indigo-600">{{ $application->control_number }}</p>
                    </div>

                    <p class="mt-4 text-sm text-gray-500">
                        {{ __('Pay via the designated payment channel using this control number. After payment is completed, click the button below to confirm and receive your trainee registration number.') }}
                    </p>

                    <form method="POST" action="{{ route('training.payment.confirm', $application) }}" class="mt-6">
                        @csrf
                        <x-primary-button type="submit">{{ __('I have completed payment') }}</x-primary-button>
                    </form>

                    <p class="mt-6">
                        <a href="{{ route('training.my-applications') }}" class="text-indigo-600 hover:text-indigo-800">
                            {{ __('View my applications') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

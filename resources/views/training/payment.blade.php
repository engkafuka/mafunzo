<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment') }} — {{ $application->course?->name }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($application->payment_verified_at)
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <p class="font-medium text-green-900">{{ __('Payment verified by WRRB staff') }}</p>
                            <p class="mt-2 text-sm text-green-800">
                                {{ __('Your payment has been approved. Your registration number is available on your applications list.') }}
                            </p>
                        </div>
                        <p class="mt-6">
                            <a href="{{ route('training.confirmation', $application) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                {{ __('View registration number') }} &rarr;
                            </a>
                        </p>
                    @elseif($application->hasControlNumber())
                        <p class="text-gray-600 mb-6">{{ __('Use the control number below to complete your payment.') }}</p>

                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-sm font-medium text-gray-700">{{ __('Control Number') }}</p>
                            <p class="mt-1 text-2xl font-mono font-bold text-indigo-600">{{ $application->control_number }}</p>
                        </div>

                        <p class="mt-4 text-sm text-gray-500">
                            {{ __('Pay via the designated payment channel using this control number. After you pay, wait for WRRB staff to verify your payment. Your registration number will appear once payment is verified.') }}
                        </p>

                        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="font-medium text-amber-950">{{ __('Awaiting staff payment verification') }}</p>
                            <p class="mt-1 text-sm text-amber-900">
                                {{ __('There is no self-confirm step. Staff will verify payment after they receive confirmation from the payment channel.') }}
                            </p>
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="font-medium text-amber-950">{{ __('Waiting for control number') }}</p>
                            <p class="mt-2 text-sm text-amber-900">
                                {{ __('WRRB staff will issue your 12-digit payment control number. You will be notified when it is ready.') }}
                            </p>
                        </div>
                    @endif

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
